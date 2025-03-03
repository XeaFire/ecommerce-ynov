# Symfony Project

## Démarrage du serveur

Pour démarrer le serveur Symfony avec le fichier php.ini local, exécutez simplement le script `start.bat` :

```bash
.\start.bat
```

Ce script va :
1. Utiliser le fichier php.ini local du projet (qui a l'extension fileinfo activée)
2. Nettoyer le cache Symfony
3. Démarrer le serveur Symfony sans TLS

## Problème de l'extension fileinfo

Le fichier php.ini local du projet a déjà l'extension fileinfo activée, ce qui résout l'erreur "Unable to guess the MIME type as no guessers are available" lors du téléchargement d'images.

## Configuration PHP

Le fichier php.ini local contient les configurations suivantes :
- Extension fileinfo activée
- Temps d'exécution maximum : 120 secondes
- Limite de mémoire : 256 Mo
- Optimisations pour Symfony (opcache, etc.)
- Configuration SSL pour curl

## Arrêt du serveur

Pour arrêter le serveur, appuyez simplement sur `Ctrl+C` dans la console où le serveur est en cours d'exécution. 