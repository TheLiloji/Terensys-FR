# SAISIES

Ce plugin permet de gérer des saisies de formulaire.

Il propose notamment des mécanismes de vérifications automatique et d'affichage conditionnel.

Documentation complète sur https://contrib.spip.net/Saisies.

# Tests unitaires
Pour executer avec PHPunit les tests unitaires


1. `composer install` (la tout première fois)
2. `vendor/bin/phpunit` pour faire tout les tests
3. `XDEBUG_MODE=coverage vendor/bin/phpunit tests/ --coverage-html coverage` pour avoir un rapport de couverture, puis ouvrir `coverage/index.html` avec un navigateur.
