<h1>Stack de développement Symfony de la SAE3</h1>

--- 
Contenu : 
- [Prérequis](#prérequis)
- [Démarrage](#démarrage)
  - [1. Forker le modèle de stack](#1-forker-le-modèle-de-stack)
  - [2. Cloner la stack du projet](#2-cloner-la-stack-du-projet)
  - [3. Démarrer la stack du projet](#3-démarrer-la-stack-du-projet)
- [Initialiser le service `sfapp`](#initialiser-le-service-sfapp)
- [Partager le projet](#partager-le-projet)

--- 

## Prérequis

Sur votre machine Linux ou Mac :

- Docker 24 
- Docker Engine sous Linux (ne pas installer Docker Desktop sous Linux)
- Docker Desktop sous Mac
- PHPStorm  
  _Votre email étudiant vous permet de bénéficier d'une licence complète de 12 mois pour tous les produits JetBrains_  

De manière optionnelle, mais fortement recommandée :

- Une [clé SSH](https://forge.iut-larochelle.fr/help/ssh/index#generate-an-ssh-key-pair) active sur votre machine
  (perso) et [ajoutée dans votre compte gitlab](https://forge.iut-larochelle.fr/help/ssh/index#add-an-ssh-key-to-your-gitlab-account) :  
  elle vous permettra de ne pas taper votre mot de passe en permanence.

## Démarrage

### 1. Forker le modèle de stack

**UN.E SEUL.E** des développeuses/développeurs de votre équipe va **fork** le présent dépôt, pour en créer un nouveau, 
dans le groupe correspondant à votre équipe :  
_Par exemple pour l'équipe 1 du groupe de TP K1, le groupe est :_ `2024-2025-BUT-INFO2-A-SAE34/K1/K11`

**Remarque** : 
>Il n'est pas nécessaire de conserver le lien avec le modèle de stack, vous pouvez donc aller dans  
> Settings > General > Advanced (dans Gitlab) pour supprimer le "Fork relationship" de votre projet


### 2. Cloner la stack du projet 

Le membre de l'équipe qui a réalisé le fork, doit cloner ce dépôt sur son poste de travail 

⚠️ **Si vous êtes sous Linux**  
> Avant de démarrer la stack, il faut renseigner les variables qui se trouvent dans le fichier `.env` à la racine du dépôt     
> Vous pouvez obtenir l'id de votre user (et de son groupe) en lançant la commande `id -u ${USER}` dans un terminal

### 3. Démarrer la stack du projet 

Dans un terminal positionné dans le dossier de la stack du projet : 

- Créer le dossier `sfapp`
```
mkdir sfapp
```

- démarrer la stack    
```
docker compose up --build
```

- inspecter l'état des services 
```
docker compose ps
```

## Initialiser le service `sfapp`

Dans un terminal positionné dans le dossier de la stack du projet : 
 
 - on se connecte au conteneur associé su service `sfapp` 
```bash
docker compose exec sfapp bash
```
- après connexion, on doit être dans `/app`, vérifier 
```
pwd 
```
- créer le projet `sfapp`
```
composer create-project symfony/skeleton:"6.3.*" sfapp
```

- vérifier l'exécution du service `sfapp`
```
localhost:8000
```

## Partager le projet

À ce stade, les services `sfapp`, `database` et `nginx` sont créés et démarrés, autrement dit fonctionnels, alors : 
- on fait `commit` et `push` pour partager avec les autres membres de l'équipe
- on déclare tout les membres de l'équipe dans le dépôt du projet avec le rôle `Developer` (si ce n'est pas déjà fait :-))
- chaque membre de l'équipe peut alors 
  - cloner ce nouveau dépôt sur son poste de travail 
  - démarrer toute la stack docker du projet 
