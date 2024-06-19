# ArticlesApp

**Auteur :** [@igorcyberdyne](https://github.com/igorcyberdyne)

**ArticlesApp** est une mini application qui permet de récupérer des articles provenant de diverses sources telle que des
API externes, des flux RSS et des fichier locaux.
Ces articles sont ensuite traités et stockés dans une base de données.

Ci-dessous la liste des ressources :
- Création d'un utilisateur (https://developers.ar24.fr/doc/#post-create-user)
- Récupération des informations d'un utilisateur (https://developers.ar24.fr/doc/#get-get-user-info)
- Ajout d'une pièce jointe (https://developers.ar24.fr/doc/#post-upload-an-attachment)
- Envoi d'un courrier avec une pièce jointe - Regular Registered Mail ( https://developers.ar24.fr/doc/#post-send-a-simple-or-eidas-registered-mail)
- Récupération des informations d'un courrier (https://developers.ar24.fr/doc/#get-get-registered-mail-info)
- Déchiffrement du résultat des requêtes (https://developers.ar24.fr/doc/#api-encryption)

## Comment ça marche ?
###  Téléchargement du projet
> Télécharger le projet en [cliquant ici](https://github.com/igorcyberdyne/article-application.git)

OU exécuter la commande ci-dessous dans la console

    git clone https://github.com/igorcyberdyne/article-application.git

###  Installation des composants du projet
Une fois le projet installé, exécuter à la racine du projet la commande suivante

    composer install

### Insertion des articles dans la base de données :

#### 1. Insertion par commande :
La command permet d'insérer les articles venant des API externe cités plus haut

    php demo/console app:load-articles


#### 2. Insertion par fixtures :
La command permet de créer des fakes articles et utilisateurs. (Cf le fichier `src\DataFixtures\AppFixtures.php`)

    php demo/console doctrine:fixtures:load


### ----------- Tout est prêt à être exploité via les API ! -----------

Ceci est la liste des API disponibles.

    1) Articles : Lister les articles et faire la recherche par : le titre, nom de l'auteur et la source de l'article
    2) ArticleById : Recherche un article par son ID
    3) CreateArticle : Créer un article
    4) UpdateArticle : Mettre à jour un article
    5) DeleteArticle : Supprimer un article
    5) ApiLogin : Se connecter à l'API pour obtenir un accessToken


### Notez bien 
- Pour utiliser les end-points sur les méthodes `DELETE`, `POST` et `PUT`, il faudra récupérer le `accessToken` de connexion
  et envoyer l'autorisation dans le header de type `'Bearer accessToken...'`
- Dans le projet il y'a un fichier pour la `collection des API` et pour les `variables d'environnement`,
que vous pouvez importer dans `POSTMAN`. 
Ces fichiers sont respectivement `api-collection.json` et `environnement.json` 
  - Fichier `environnement.json`
    - la variable `accessToken` sera pré-remplie si vous exécutez le end-point `ApiLogin`
    - la variable `host` est à remplacer par le votre (Ex: http://localhost.com)
