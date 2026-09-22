# Tableau de bord

Le tableau de bord est le moyen le plus rapide de voir comment évolue votre activité téléphonique,
sans avoir à choisir d'abord une file d'attente ou un utilisateur précis. Il comporte deux onglets :
**Analyse de période** et **Tendances sur 12 mois**.

## Onglet Analyse de période

Choisissez une période dans le sélecteur — **Aujourd'hui, Hier, Cette semaine, Semaine dernière, Ce
mois, Mois dernier, 3 derniers mois, 6 derniers mois** — et tout l'onglet se met à jour.

**Widget Total des appels** — affiche le nombre total d'appels et leur durée totale, répartis en
Entrants, Sortants et Internes, avec une note précisant que la classification externe/interne
dépend de la configuration de votre PBX.

**Widget Appels entrants** — deux vues, par file d'attente et par utilisateur (poste), affichant
chacune :
- **Répondus** vs **Non répondus** sous forme de barres empilées, basculables **Par heure** /
  **Par jour**.
- **Appels perdus** — appels jamais répondus *et* jamais abandonnés en pré-décroché (c'est-à-dire
  que l'appelant a raccroché en attendant réellement un agent).
- **Temps de réponse moyen** et **Appels courts** (répondus en moins de 10 secondes — généralement
  soit des décrochés rapides chanceux, soit des raccrochages accidentels, à surveiller si le
  nombre est élevé).
- Un bouton **« Consolidation multi-files »** — lorsque l'appel d'un correspondant traverse
  plusieurs files d'attente avant d'être répondu, cette option le compte une seule fois au lieu
  d'une fois par file, afin que vous voyiez le nombre réel d'appels entrants distincts plutôt qu'un
  décompte gonflé par file.

Trois cartes en jauge radiale résument la période en un coup d'œil : **Appels perdus**, **Temps de
réponse moyen**, et **Appels courts (≤10s)** — chacune affichée en pourcentage du total des appels,
pour évaluer visuellement la santé de la période sans lire les chiffres exacts.

**Widget Appels sortants** — même répartition répondus/non répondus pour les appels passés par
votre équipe.

**Widget Top 10 utilisateurs** — vos postes les plus actifs sur la période, avec des notes de bas
de page expliquant précisément comment les appels entrants, sortants et internes sont attribués à
chaque utilisateur (utile si un chiffre semble plus élevé ou plus bas que prévu).

## Onglet Tendances sur 12 mois

Deux courbes de tendance, comparant toutes deux le mois en cours au même mois de l'année dernière,
ou à la plus longue période d'historique disponible :

- **Tendance des appels répondus** — combine le Total des appels (barres), les Appels répondus
  (courbe) et le **Taux de réponse %** (courbe) sur un même graphique, pour voir volume et qualité
  ensemble.
- **Tendance du temps d'attente moyen** — une seule courbe montrant l'évolution du temps d'attente
  des appelants, mois après mois.

Au-dessus des graphiques, trois chiffres de comparaison montrent ce mois vs. le mois dernier pour
le **Total des appels**, le **Taux de réponse aux appels**, et le **Temps d'attente moyen**, chacun
avec une flèche de tendance et un pourcentage de variation.

## Cartes d'analyse

Sous les graphiques principaux, vous verrez des encarts d'analyse en langage clair, par exemple
*« X appels sur Y ont été perdus »*, une explication de ce que « appels perdus » exclut (les appels
dissuadés/abandonnés sont comptés séparément), et une note sur ce qui constitue un « appel court »
et pourquoi cela mérite d'être examiné.

## Actions rapides

En haut du tableau de bord :

- **Envoyer le rapport** — envoie la vue actuelle par e-mail une fois, à la personne de votre choix.
  Voir [Rapports programmés & partage](11-scheduled-reports-and-sharing.md).
- **Programmer le rapport** — configure un envoi récurrent de cette vue (quotidien/hebdomadaire/mensuel).
- **Voir les rapports** — accède aux **Rapports programmés** pour gérer les programmations
  existantes.
- **Créer une alerte** — vous dirige vers la configuration des alertes IA (voir
  [Analyses IA](09-ai-insights.md)).

> Remarque : Envoyer/Programmer un rapport nécessite `expert-statistics.modify` — voir
> [Permissions & accès](02-permissions.md).

## Pages associées

- Pour le détail par file d'attente ou par utilisateur derrière ces chiffres, voir
  [Mes files d'attente](04-my-queues.md) et [Mes utilisateurs](05-my-users.md).
- Pour la signification de chaque KPI, voir le
  [Glossaire des KPI & rapports](13-kpi-and-report-glossary.md).
