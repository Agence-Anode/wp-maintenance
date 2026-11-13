# Connaissances du projet

Contexte qui ne se lit pas dans le code. Reporté depuis la mémoire des agents le 2026-11-13, vérifié contre le code à cette date.

## Anode Warning

- **Pourquoi l’intégration à All-in-One WP Migration** (v2) : le plugin était rangé dans Réglages et on oubliait de l’activer avant une maintenance. Le menu est donc placé là où l’on va pour sauvegarder le site : sous-menu de l’export AIWM (`ai1wm_export`, détecté par la constante `AI1WM_PLUGIN_NAME`), sinon dans Outils (`tools.php`). Voir `anode-warning/anode-warning.php`.
- **Prochaine étape prévue** (non faite au 2026-11-13) : la même intégration avec WPVivid, l’autre extension de sauvegarde du parc.
- **Charte Anode** utilisée par le bandeau et l’écran d’admin (`anode-warning.css`) : `#6B54FF` (principale), `#1A1B23` (sombre), `#ECF890` (accent), `#C7B5F3` (secondaire), police Poppins.
- Le dossier `info/` (ignoré par git) sert à garder en local les plugins tiers de référence (AIWM, WPVivid) pour étudier leurs menus et hooks. Il n’est pas dans le clone.
