
## Authentification

En tant qu'utilisateur (apprenti, coach, formateur ou admin),
Je veux me connecter avec mon email et mon mot de passe,
Afin d'accéder aux fonctionnalités de mon rôle.

En tant qu'utilisateur connecté,
Je veux rester connecté lorsque je recharge la page ou navigue dans l'application,
Afin de ne pas avoir à me reconnecter constamment.

En tant qu'utilisateur connecté,
Je veux me déconnecter,
Afin de terminer ma session en toute sécurité sur un appareil partagé.

En tant qu'utilisateur connecté,
Je veux pouvoir changer mon mot de passe depuis mes paramètres à tout moment,
Afin de garder mon compte sécurisé.

En tant qu'utilisateur ayant oublié mon mot de passe,
Je veux voir un message m'indiquant de contacter mon administrateur,
Afin de savoir comment récupérer l'accès puisqu'il n'y a pas de réinitialisation automatique.

En tant qu'administrateur,
Je veux que chaque rôle soit restreint aux pages et actions qui lui correspondent,
Afin que les apprentis, coaches et formateurs ne puissent pas voir ou agir sur des données hors de leurs permissions.

En tant qu'utilisateur,
Je veux pouvoir être connecté sur plusieurs onglets ou appareils en même temps,
Afin de ne pas être déconnecté si j'utilise l'application ailleurs.

En tant qu'administrateur,
Je veux que les mots de passe respectent des règles de sécurité (8 caractères minimum, 1 majuscule, 1 chiffre),
Afin de garantir la protection des comptes.

En tant qu'administrateur,
Je veux qu'un compte soit temporairement bloqué après 5 tentatives de connexion échouées pendant 15 minutes,
Afin de protéger contre les attaques par force brute.

En tant qu'utilisateur,
Je veux être redirigé vers la page correspondant à mon rôle après la connexion,
Afin d'accéder directement à mon espace sans navigation manuelle.

En tant qu'utilisateur,
Je veux que changer mon mot de passe déconnecte toutes les autres sessions actives,
Afin que personne ne puisse continuer à utiliser mon ancien mot de passe.

En tant qu'utilisateur,

me connectant pour la première fois avec un mot de passe créé par un administrateur, je souhaite être invité à définir mon propre mot de passe, afin que le mot de passe temporaire ne reste pas en place indéfiniment.


## Administration

En tant qu'administrateur,
Je veux créer des comptes (email + mot de passe initial) pour les apprentis, coaches et formateurs,
Afin qu'ils puissent se connecter sans s'inscrire eux-mêmes.

En tant qu'administrateur,
Je veux pouvoir éditer ou désactiver un compte existant,
Afin de corriger des erreurs ou supprimer l'accès de quelqu'un qui est parti.

En tant qu'administrateur,
Je veux assigner un coach à chaque apprenti,
Afin que le bon coach puisse suivre leurs progrès.

En tant qu'administrateur,
Je veux assigner un formateur à chaque apprenti basé sur la section,
Afin que le bon formateur puisse réviser leur travail.

En tant qu'administrateur,
Je veux définir l'année et la section de chaque apprenti,
Afin que le système applique les bonnes règles et le bon contenu à leur profil.

En tant qu'administrateur,
Je veux définir des plages de dates pour chaque année et semestre,
Afin que l'application sache automatiquement dans quel semestre un apprenti se trouve lorsqu'il upload une note.

En tant qu'administrateur,
Je veux ajouter, modifier ou supprimer les matières disponibles pour les notes,
Afin que la section des notes reflète les matières réellement enseignées.

En tant qu'administrateur,
Je veux ajouter, modifier ou supprimer des entrées dans le catalogue de compétences informatique,
Afin que les apprentis sélectionnent des compétences précises et standardisées pour leurs projets.

En tant qu'administrateur,
Je veux voir une liste de tous les apprentis avec leur coach assigné, leur formateur, leur année et leur section,
Afin de vérifier les assignments et repérer ceux qui en manquent.

En tant qu'administrateur,
Je veux avoir un tableau de bord avec une vue d'ensemble (nombre d'apprentis, coaches, formateurs, notes récentes),
Afin de suivre l'activité globale de l'application.

En tant qu'administrateur,
Je veux ne PAS pouvoir me désactiver moi-même,
Afin d'éviter de bloquer tous les comptes par erreur.

En tant qu'administrateur,
Je veux qu'une matière liée à des notes ne puisse pas être supprimée mais seulement désactivée,
Afin de ne pas perdre l'historique des notes existantes.

En tant qu'administrateur,
Je veux que le calendrier académique valide l'absence de chevauchement et de trous entre les périodes,
Afin que chaque date tombe dans exactement une période.

En tant qu'administrateur,
Je veux pouvoir créer un autre administrateur,
Afin de pouvoir partager la gestion si besoin.

En tant qu'administrateur,
Je veux pouvoir désactiver un autre administrateur avec une confirmation obligatoire,
Afin d'éviter les désactivations accidentelles.


## Capture des notes

En tant qu'apprenti,
Je veux uploader le PDF scanné de ma copie notée,
Afin que le document original fasse partie de mon dossier officiel.

En tant qu'apprenti,
Je veux choisir à quelle matière mon test appartient,
Afin qu'il soit classé sous le bon sujet.

En tant qu'apprenti,
Je veux entrer la note que j'ai reçue (entre 1 et 6, par paliers de 0.5),
Afin qu'elle soit enregistrée à côté du PDF scanné.

En tant qu'apprenti,
Je veux entrer la date du test,
Afin que l'application puisse déterminer à quel semestre il appartient.

En tant qu'apprenti,
Je veux qu'une note hors des limites soit refusée lors de la soumission,
Afin qu'une erreur de frappe ne corrompe pas silencieusement ma moyenne.

En tant qu'apprenti,
Je veux que l'application dérive automatiquement l'année et le semestre à partir de la date,
Afin de ne pas avoir à savoir dans quel semestre une date se situe.

En tant qu'apprenti,
Je veux que mon PDF uploadé soit automatiquement renommé selon la convention de l'entreprise,
Afin que les coaches et formateurs puissent identifier les fichiers de manière cohérente en dehors de l'application.

En tant qu'apprenti,
Je veux que le scan renommé soit stocké par l'application,
Afin que mon coach et formateur puissent ouvrir le fichier original plus tard.

En tant qu'apprenti,
Je veux que l'URL du fichier stocké soit protégée par les mêmes règles que la page de la note,
Afin que connaître le nom du fichier ne suffise pas à quelqu'un d'autre pour lire ma copie.

En tant qu'apprenti,
Je veux qu'un fichier qui n'est pas un PDF soit refusé à l'upload côté client et côté serveur,
Afin que seuls les scans valides soient stockés.

En tant qu'apprenti,
Je veux que la taille du fichier soit limitée à 10 Mo,
Afin d'éviter les uploads de fichiers trop volumineux.

En tant qu'apprenti,
Je veux qu'une date dans le futur soit refusée,
Afin qu'on ne puisse pas soumettre un test qui n'a pas encore eu lieu.

En tant qu'apprenti,
Je veux qu'une matière désactivée par l'administrateur ne soit plus sélectionnable lors de la soumission,
Afin de ne pas pouvoir soumettre une note sous une matière obsolète.

En tant qu'apprenti,
Je veux voir un message d'erreur clair si l'upload échoue,
Afin de comprendre ce qui s'est passé et réessayer.

En tant qu’apprenti,
je veux voir les commentaires laissés par mon coach ou mon formateur sur mes notes,
afin de comprendre leur feedback sans avoir à chercher dans mes e-mails.

En tant qu’apprenti,
je veux voir le PDF de ma copie directement dans le navigateur,
afin de vérifier le scan original sans avoir à télécharger le fichier.

En tant qu’apprenti,
je veux pouvoir filtrer mes notes par matière,
afin de retrouver rapidement les notes d’un sujet spécifique sans scroller dans toute ma liste.


En tant qu'apprenti,
je veux marquer une note comme (par exemple un examen oral) afin de pouvoir la soumettre sans PDF,
afin que les résultats oraux puissent toujours être enregistrés même s'il n'y a rien à scanner.

---


## Mon dossier de notes

En tant qu'apprenti,
Je veux voir toutes mes notes groupées par matière,
Afin de suivre mes progrès sujet par sujet.

En tant qu'apprenti,
Je veux pouvoir ouvrir une note et voir le PDF rendu dans la page,
Afin de vérifier le scan original sans avoir à télécharger un fichier.

En tant qu'apprenti,
Je veux voir une moyenne calculée automatiquement pour chaque matière,
Afin de savoir comment je me débrouille dans cette matière sans avoir à la calculer moi-même.

En tant qu'apprenti,
Je veux voir une moyenne globale unique calculée à partir de mes moyennes par matière,
Afin d'avoir un seul nombre qui reflète ma performance globale.
(La formule de calcul est délibérément reportée à définir dans un second temps.)

En tant qu'apprenti,
Je veux pouvoir corriger une note que j'ai déjà soumise,
Afin de réparer une erreur sans demander à un admin.

En tant qu'apprenti,
Je veux que le fichier renommé soit mis à jour automatiquement si je change la matière ou la note lors d'une édition,
Afin que le nom du fichier ne contredise jamais l'enregistrement.

En tant qu'apprenti,
Je veux pouvoir supprimer une note que j'ai ajoutée par erreur,
Afin que mon dossier et mes moyennes ne soient pas faussés.

En tant qu'apprenti,
Je veux que la note d'un autre apprenti me soit refusée plutôt que simplement cachée,
Afin que mon dossier soit réellement privé.

En tant qu'apprenti,
Je veux voir un message explicite et un bouton pour ajouter ma première note quand je n'en ai aucune,
Afin de ne pas être face à une page vide sans savoir quoi faire.

En tant qu'apprenti,
Je veux que les notes au sein d'une même matière soient triées chronologiquement,
Afin de voir facilement mon évolution.

En tant qu'apprenti,
Je veux que les commentaires existants restent attachés à une note quand je l'édite,
Afin que le feedback de mon coach ou formateur ne soit pas perdu.

---
## Révision par coach/formateur

En tant que coach,
Je veux voir la liste des apprentis qui me sont assignés,
Afin d'avoir un point d'entrée vers le dossier de chacun.

En tant que coach,
Je veux ouvrir les notes d'un apprenti groupées par matière avec leurs moyennes,
Afin de réviser ses progrès comme il les voit lui-même.

En tant que coach,
Je veux ouvrir une note spécifique et voir le PDF scanné,
Afin de regarder la copie réelle et pas seulement le chiffre.

En tant que formateur,
Je veux le même accès (liste, notes, scans) pour les apprentis qui me sont assignés,
Afin de suivre leurs progrès sans outil séparé.

En tant que coach ou formateur,
Je veux qu'un apprenti qui n'est pas assigné me soit refusé,
Afin que la frontière d'assignment soit réelle et pas juste un lien que je n'ai pas vu.

En tant que coach ou formateur,
Je veux que les données de note soient en lecture seule pour moi,
Afin que le dossier de l'apprenti reste le sien et qu'il n'y ait pas de confusion sur qui a entré quoi.

En tant que coach ou formateur,
Je veux pouvoir voir les commentaires laissés par l'autre rôle,
Afin d'avoir une vue complète du feedback donné à l'apprenti.

En tant que coach ou formateur,
Je veux qu'un apprenti désactivé disparaisse de ma liste mais que ses notes restent archivées et accessibles,
Afin de ne pas polluer ma vue active tout en gardant l'historique.

---


## Feedback / Commentaires

En tant que coach,
Je veux pouvoir laisser un commentaire sur une note spécifique,
Afin de donner un feedback ciblé en particulier sur une mauvaise note.

En tant que formateur,
Je veux pouvoir laisser un commentaire sur une note spécifique,
Afin de donner un feedback sur le travail dont je suis responsable.

En tant qu'apprenti,
Je veux voir les commentaires laissés sur mes notes juste en dessous du scan,
Afin de comprendre le feedback sans chercher dans mes emails.

En tant qu'apprenti,
Je veux que chaque commentaire affiche son auteur et la date à laquelle il a été écrit,
Afin de savoir s'il vient de mon coach ou de mon formateur.

En tant qu'apprenti,
Je veux que le fil de commentaires se mette à jour automatiquement avec les nouveaux commentaires,
Afin de ne pas avoir à recharger la page pour vérifier s'il y a du nouveau feedback.

En tant que coach ou formateur,
Je veux pouvoir modifier un commentaire que j'ai laissé,
Afin de corriger une erreur ou reformuler ma pensée.

En tant qu'administrateur,
Je veux pouvoir supprimer un commentaire inapproprié,
Afin de maintenir la qualité des échanges.

En tant que coach ou formateur,
Je veux que le corps du commentaire soit limité à 2000 caractères,
Afin d'éviter les commentaires excessivement longs.

En tant qu'apprenti,
Je veux que les commentaires restent attachés à une note même si cette note est éditée,
Afin que le feedback ne soit jamais perdu.


---

## Notifications

En tant que coach,
Je veux recevoir un email automatiquement quand un apprenti que je suis ajoute une note,
Afin de savoir qu'il y a quelque chose à réviser sans avoir à vérifier l'application.

En tant que formateur,
Je veux recevoir le même email pour les apprentis qui me sont assignés,
Afin d'être informé des nouvelles notes au moment où elles arrivent.

En tant que coach ou formateur,
Je veux que le PDF renommé soit joint à l'email de notification,
Afin de pouvoir jeter un œil à la copie sans avoir à se connecter.

En tant qu'apprenti,
Je veux recevoir un email automatiquement quand un coach ou formateur commente l'une de mes notes,
Afin d'être averti sans avoir à ouvrir l'application constamment.

En tant que coach ou formateur,
Je veux pouvoir désactiver les notifications email,
Afin de ne pas être inondé si je préfère vérifier l'application manuellement.

En tant que coach ou formateur,
Je veux recevoir un email quand une note qui m'a été notifiée est supprimée par l'apprenti,
Afin de savoir que la copie n'est plus dans le système.

En tant que coach ou formateur,
Je veux qu'une note éditée par l'apprenti ne déclenche pas de nouvelle notification email,
Afin de ne pas recevoir un deuxième email pour la même note.

En tant que coach ou formateur,
Je veux que l'email de notification contienne le nom de l'apprenti, la matière, la note, la date et un lien direct vers la page de la note,
Afin d'avoir toutes les infos utiles d'un coup.

---

## Dossier de formation

En tant qu'apprenti informatique,
Je veux ajouter un projet avec un titre et une description,
Afin de documenter le travail que j'ai réalisé.

En tant qu'apprenti informatique,
Je veux sélectionner les compétences informatique que j'ai acquises sur un projet (mobilisée ou développée) depuis un catalogue prédéfini,
Afin que mon dossier reflète des compétences standardisées.

En tant qu'apprenti informatique,
Je veux ajouter plusieurs projets à mon dossier au fil du temps,
Afin qu'il devienne un dossier de formation complet.

En tant qu'apprenti informatique,
Je veux que mes données de projet remplissent automatiquement un aperçu HTML dans le format officiel,
Afin de voir mon dossier sans formattage manuel.

En tant que développeur,
Je veux un template HTML/CSS réutilisable et professionnel pour l'aperçu du dossier,
Afin que les données des projets s'affichent de manière cohérente et dans le style de l'entreprise.

En tant qu'apprenti informatique,
Je veux que l'aperçu HTML se mette à jour automatiquement quand j'ajoute un nouveau projet,
Afin de ne jamais voir une version périmée.

En tant qu'apprenti informatique,
Je veux un bouton pour sauvegarder mon aperçu de dossier en PDF,
Afin d'avoir une copie portable que je peux partager ou soumettre.

En tant qu'apprenti informatique,
Je veux pouvoir modifier un projet déjà ajouté,
Afin de corriger ou mettre à jour ses détails.

En tant qu'apprenti informatique,
Je veux pouvoir supprimer un projet déjà ajouté,
Afin de retirer une entrée qui ne fait plus partie de mon dossier.

En tant que coach,
Je veux voir le dossier des apprentis qui me sont assignés,
Afin de réviser leurs projets documentés et leurs compétences.

En tant que formateur,
Je veux voir le dossier des apprentis qui me sont assignés,
Afin de réviser leurs projets documentés et leurs compétences.

En tant qu'administrateur,
Je veux pouvoir voir le dossier de n'importe quel apprenti,
Afin de superviser les dossiers de formation à travers l'organisation.

En tant que coach, formateur ou administrateur,
Je veux pouvoir laisser un commentaire sur un projet spécifique du dossier d'un apprenti,
Afin de donner un feedback sur leur travail.

En tant qu'apprenti informatique,
Je veux voir les commentaires laissés sur les projets de mon dossier,
Afin de comprendre le feedback reçu.

En tant qu'apprenti informatique,
Je veux que chaque projet contienne titre, organisation, date de début et fin, description, technologies utilisées, rôle, lien démo, lien code source, et captures d'écran,
Afin que mon dossier soit complet et détaillé.

En tant qu'apprenti informatique,
Je veux que les projets soient affichés en ordre chronologique par date de début,
Afin de suivre l'évolution de mon travail au fil du temps.

En tant qu'apprenti informatique,
Je veux que si une compétence est retirée du catalogue, les projets existants qui la référencent la conservent,
Afin que l'historique ne soit pas altéré.

En tant qu'apprenti désactivé,
Je veux que mon dossier reste accessible à mon coach et formateur,
Afin que leur suivi ne soit pas interrompu.


En tant qu'apprenti en informatique,
je souhaite réorganiser mes projets 
afin de pouvoir les présenter dans l'ordre que je préfère.

---




