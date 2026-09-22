# Paramètres PBX

**Paramètres PBX** est une page unique à onglets regroupant toute la configuration d'Expert
Statistics qui ne concerne pas la gestion des hôtes PBX elle-même (les détails de connexion des
hôtes relèvent exclusivement des administrateurs — voir le guide revendeur/administrateur). Toute
personne disposant de `expert-statistics.view` peut ouvrir chaque onglet et lire sa configuration
actuelle ; une bannière en lecture seule apparaît et chaque contrôle Enregistrer/Créer/Supprimer est
désactivé ou masqué à moins d'avoir également `expert-statistics.modify` (voir
[Permissions & accès](02-permissions.md)).

## Onglet Hôte actif

Si votre compte possède plusieurs hôtes PBX, c'est ici que vous choisissez celui que toutes les
pages de rapport interrogent actuellement. Sélectionnez un hôte dans la liste déroulante et cliquez
sur **Enregistrer**. Ce choix affecte uniquement ce que vous voyez en parcourant les rapports — il
ne modifie ni le statut d'activation ni la configuration de l'hôte.

## Onglet Agent

Deux champs texte libres, **Personnalisé 1** et **Personnalisé 2**, permettent de renommer les deux
statuts d'agent personnalisés affichés dans toute la Supervision des agents et l'onglet Statut de
Mes utilisateurs (par exemple renommer « Personnalisé 1 » en « Formation » ou « Pause »). Cliquez
sur **Enregistrer**.

## Onglet File d'attente

Configurez le **temps de pré-décroché** — une durée (en secondes) appliquée à une ou plusieurs files
d'attente, utilisée pour ajuster la façon dont le « temps de réponse » est mesuré pour ces files.
Sélectionnez les files auxquelles l'appliquer, saisissez la durée, et **Enregistrez**. Sous le
formulaire :

- Sous-onglet **Actuel** — les enregistrements de temps de pré-décroché actifs (file, secondes,
  créé par et quand), chacun avec une option de suppression ; vous pouvez aussi en sélectionner
  plusieurs et **Supprimer la sélection**.
- Sous-onglet **Historique** — un journal consultable et paginé des anciens enregistrements de
  temps de pré-décroché.

## Onglet Tableau de rapport

Deux groupes de paramètres contrôlant le calcul et la coloration des tableaux de rapport :

- **Intervalles de temps** — Plage de temps (10–140s, le premier intervalle), Écart de temps
  (10–40s, la taille de chaque intervalle suivant), et Colonnes (1–5, combien d'intervalles
  afficher). Ensemble, ils définissent comment les durées d'appel sont regroupées dans les rapports
  de type intervalle.
- **Seuils d'alerte visuelle** — pour cinq indicateurs (Taux de réponse %, Temps d'attente moyen –
  file, Temps d'attente moyen – utilisateur, Durée moyenne d'appel, Ratio de sollicitation
  d'appels), définissez des seuils numériques pour quatre niveaux de couleur (Rouge / Orange / Jaune
  / Vert), chacun activable/désactivable individuellement, avec des raccourcis **Tout activer** /
  **Tout désactiver** par indicateur. Ces seuils déterminent la coloration que vous voyez sur les
  tableaux de rapport dans tout le module.

Cliquez sur **Enregistrer** pour appliquer.

## Onglet Alertes IA

Contrôles pour les alertes décrites dans [Analyses IA](09-ai-insights.md#alertes-ia) :

- **Activé** — active/désactive entièrement les alertes IA.
- **Intervalle de vérification** — à quelle fréquence (15–1440 minutes) le système réévalue les
  conditions d'alerte.
- **Langue** et **E-mail de notification** — où et dans quelle langue les notifications d'alerte
  sont envoyées.
- **Seuils** — Taux d'abandon (Avertissement/Critique %), Taux de non-réponse
  (Avertissement/Critique %), Taux d'abandon en pré-décroché (Avertissement %), Temps d'attente
  (Avertissement/Critique, secondes), et Seuils de changement (Changement de volume % / Changement
  du taux d'abandon %) — la sensibilité pour détecter un changement soudain plutôt qu'un
  dépassement en régime stable.

Cliquez sur **Enregistrer** pour appliquer.

## Onglet Groupes

Regroupez vos files d'attente, postes, numéros DID, ou numéros appelants en **Groupes de
ressources** nommés, afin de pouvoir générer des rapports ou programmer des rapports sur un
ensemble cohérent (par ex. les postes de « l'équipe commerciale », ou les DID du « bureau
principal ») plutôt que de sélectionner les membres un par un à chaque fois.

- Quatre sous-onglets : **Files d'attente**, **Postes**, **Numéros DID**, **Numéros appelants**.
- **+ Nouveau groupe** — nommez le groupe, puis choisissez ses membres : une liste à cocher pour
  files/postes/DID, ou un sélecteur de recherche avec ajout par étiquette pour les numéros
  appelants.
- Les groupes existants apparaissent dans un tableau avec des étiquettes de membres (affichant
  quelques-uns, avec un « +N » pour le surplus), et des actions **Modifier**/**Supprimer** par
  ligne.

Les groupes de ressources que vous créez ici deviennent sélectionnables lors de la création ou
modification d'un [Rapport programmé](11-scheduled-reports-and-sharing.md).

## Onglet Wallboard

Voir [Wallboard & données en direct](12-wallboard-live-data.md) pour le guide complet — cet onglet
est l'endroit où les wallboards se construisent, se prévisualisent, et se partagent.

## Pages associées

- Ce que chaque niveau de permission vous permet de faire sur cette page :
  [Permissions & accès](02-permissions.md).
- Utiliser les groupes de ressources lors de la programmation d'un rapport :
  [Rapports programmés & partage](11-scheduled-reports-and-sharing.md).
