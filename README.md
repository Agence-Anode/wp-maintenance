# WP Maintenance

Plugins WordPress maison d'[Agence Anode](https://agence-anode.fr/) pour la gestion de maintenance du parc de sites.

## Plugins

### Anode Warning

Affiche un bandeau de maintenance aux administrateurs (frontend et backend).

**Fonctionnalités :**
- Bandeau activable/désactivable avec texte personnalisable (éditeur WYSIWYG)
- S'intègre dans le sous-menu d'All-in-One WP Migration si présent, sinon dans Outils
- Mises à jour automatiques via GitHub Releases

**Installation :**
1. Télécharger `anode-warning.zip` depuis la [dernière release](https://github.com/Agence-Anode/wp-maintenance/releases/latest)
2. Dans WordPress : Extensions → Ajouter → Téléverser une extension
3. Activer le plugin

**Utilisation :**
- Avec All-in-One WP Migration : All-in-One WP Migration → Anode Warning
- Sans All-in-One WP Migration : Outils → Anode Warning

## Release

Chaque plugin est buildé et zippé automatiquement par la CI GitHub Actions au push d'un tag.

```bash
# Bumper la version dans le plugin header, puis :
git commit -am "bump: vX.Y.Z"
git tag X.Y.Z
git push && git push --tags
```

Le tag doit correspondre exactement au `Version:` dans le fichier PHP principal du plugin.
