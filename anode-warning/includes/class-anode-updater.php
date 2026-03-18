<?php
/**
 * Anode Updater — Mises à jour automatiques via GitHub Releases.
 *
 * Repo : agence-anode/wp-maintenance
 * Convention : tag = version nue (ex : 2.0), asset « anode-warning.zip » attaché à la release.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Anode_Updater {

    private $file;
    private $basename;
    private $plugin_data;
    private $github_repo = 'agence-anode/wp-maintenance';
    private $github_response;
    private $transient_key = 'anode_warning_github_response';
    private $cache_ttl = 43200; // 12 h

    public function __construct( $file ) {
        $this->file     = $file;
        $this->basename = plugin_basename( $file );

        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
        add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
        add_filter( 'upgrader_post_install', array( $this, 'after_install' ), 10, 3 );
    }

    /**
     * Charge les données du plugin header (lazy).
     */
    private function get_plugin_data() {
        if ( empty( $this->plugin_data ) ) {
            if ( ! function_exists( 'get_plugin_data' ) ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            $this->plugin_data = get_plugin_data( $this->file );
        }
        return $this->plugin_data;
    }

    /**
     * Interroge l'API GitHub Releases (avec cache transient).
     */
    private function get_github_release() {
        if ( ! empty( $this->github_response ) ) {
            return $this->github_response;
        }

        $cached = get_transient( $this->transient_key );
        if ( false !== $cached ) {
            $this->github_response = $cached;
            return $cached;
        }

        $url      = 'https://api.github.com/repos/' . $this->github_repo . '/releases/latest';
        $response = wp_remote_get( $url, array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
            ),
        ) );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ) );
        if ( empty( $body->tag_name ) ) {
            return false;
        }

        // Cherche l'asset anode-warning.zip dans la release.
        $download_url = '';
        if ( ! empty( $body->assets ) && is_array( $body->assets ) ) {
            foreach ( $body->assets as $asset ) {
                if ( 'anode-warning.zip' === $asset->name ) {
                    $download_url = $asset->browser_download_url;
                    break;
                }
            }
        }

        // Fallback sur le zipball du repo.
        if ( empty( $download_url ) ) {
            $download_url = $body->zipball_url;
        }

        $release = (object) array(
            'version'      => ltrim( $body->tag_name, 'v' ),
            'download_url' => $download_url,
            'changelog'    => ! empty( $body->body ) ? $body->body : '',
            'published_at' => ! empty( $body->published_at ) ? $body->published_at : '',
        );

        set_transient( $this->transient_key, $release, $this->cache_ttl );
        $this->github_response = $release;

        return $release;
    }

    /**
     * Hook : pre_set_site_transient_update_plugins
     * Injecte la mise à jour dans le transient WP si une nouvelle version existe.
     */
    public function check_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $release = $this->get_github_release();
        if ( false === $release ) {
            return $transient;
        }

        $plugin_data    = $this->get_plugin_data();
        $current_version = $plugin_data['Version'];

        if ( version_compare( $release->version, $current_version, '>' ) ) {
            $transient->response[ $this->basename ] = (object) array(
                'slug'        => 'anode-warning',
                'plugin'      => $this->basename,
                'new_version' => $release->version,
                'url'         => 'https://github.com/' . $this->github_repo,
                'package'     => $release->download_url,
            );
        }

        return $transient;
    }

    /**
     * Hook : plugins_api
     * Affiche les infos dans la modale « Détails du plugin ».
     */
    public function plugin_info( $result, $action, $args ) {
        if ( 'plugin_information' !== $action ) {
            return $result;
        }
        if ( empty( $args->slug ) || 'anode-warning' !== $args->slug ) {
            return $result;
        }

        $release = $this->get_github_release();
        if ( false === $release ) {
            return $result;
        }

        $plugin_data = $this->get_plugin_data();

        return (object) array(
            'name'            => $plugin_data['Name'],
            'slug'            => 'anode-warning',
            'version'         => $release->version,
            'author'          => $plugin_data['Author'],
            'homepage'        => $plugin_data['PluginURI'],
            'download_link'   => $release->download_url,
            'requires'        => '5.0',
            'tested'          => '6.8',
            'requires_php'    => '7.4',
            'last_updated'    => $release->published_at,
            'sections'        => array(
                'description' => $plugin_data['Description'],
                'changelog'   => nl2br( esc_html( $release->changelog ) ),
            ),
        );
    }

    /**
     * Hook : upgrader_post_install
     * Renomme le dossier extrait et réactive le plugin.
     */
    public function after_install( $response, $hook_extra, $result ) {
        if ( empty( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->basename ) {
            return $result;
        }

        global $wp_filesystem;

        $install_dir = $result['destination'];
        $proper_dir  = WP_PLUGIN_DIR . '/anode-warning';

        // Renomme si le dossier extrait ne correspond pas.
        if ( $install_dir !== $proper_dir ) {
            $wp_filesystem->move( $install_dir, $proper_dir );
            $result['destination']      = $proper_dir;
            $result['destination_name'] = 'anode-warning';
        }

        // Réactive le plugin.
        activate_plugin( $this->basename );

        return $result;
    }
}
