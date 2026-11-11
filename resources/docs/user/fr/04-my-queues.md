# Mes files d'attente

Tout ce qui concerne les appels passés par une file d'attente (un groupe d'agents se partageant les
appels entrants), avec quatre sous-pages : **Tableau de bord**, **Rapport**, **KPI**, et
**Origines**.

## Choisir vos files d'attente

Chaque page « Mes files d'attente » commence par un sélecteur de files/postes vous permettant de
choisir quelles files (et éventuellement quels postes individuels) inclure, ainsi qu'une période.

## Tableau de bord

Une version condensée du tableau de bord principal, limitée aux files d'attente sélectionnées —
même style de graphiques répondus/non répondus, cartes en jauge radiale, et bouton **Exclure les
heures de fermeture** (voir ci-dessous).

## Rapport

Un tableau triable et filtrable — une ligne par file d'attente (ou par file+poste, si vous
désactivez « Files uniquement »). Colonnes :

| Colonne | Signification |
|---|---|
| **Date** / **Période** | Affichée dans les vues Jour, Semaine et Mois (voir [Vues par période](#vues-par-periode-et-comparaison)). |
| **File** / **Poste** | La file d'attente concernée (et, si activé, le poste). |
| **Appels** | Total des appels ayant atteint cette file. |
| **Non répondus** | Réparti en *Déclinés* et *Abandonnés* — les appels déclinés n'ont pas été
  répondus par un agent ; les appels abandonnés ont été raccrochés par l'appelant avant d'être
  répondus (le nombre d'abandons en pré-décroché apparaît entre parenthèses s'il est non nul). |
| **Interne** (vue files uniquement) ou **Sollicité** (vue par poste) | Appels internes vers la
  file, ou appels spécifiquement acheminés/sollicités vers ce poste. |
| **Répondus** | Appels effectivement décrochés. |
| **Transférés** | Appels transférés ailleurs après avoir été répondus. |
| **Taux %** | Taux de réponse — coloré en vert (≥80 %), jaune (≥60 %), rouge (en dessous), calculé
  comme répondus ÷ (appels − abandons en pré-décroché). |
| **Durée conv.** | Durée totale de conversation pour cette ligne. |
| **Durée attente** | Temps total passé en attente par les appelants. |

### Options de la ligne

- **Files uniquement (sans postes)** — réduire le tableau à une ligne par file au lieu d'une ligne
  par file+poste.
- **Exclure les heures de fermeture** — filtre les appels survenus pendant les heures de
  fermeture/hors service configurées pour le PBX, afin qu'une file uniquement couverte de 9h à 17h
  ne soit pas pénalisée par le volume d'appels hors horaires.
- **Consolider** — (vue files uniquement) fusionne toutes les files sélectionnées en une seule ligne
  combinée « Consolidée », utile lorsque vous voulez un seul total plutôt qu'une répartition par
  file.
- **Consolidation multi-files** — lorsque l'appel d'un même correspondant traverse plusieurs files
  d'attente avant d'être répondu, cette option le compte une seule fois au lieu d'une fois par file
  traversée, donnant le nombre réel d'appels entrants distincts. C'est différent du bouton
  « Consolider » ci-dessus — l'un fusionne des *lignes*, l'autre dédoublonne des *appels*. Vous
  pouvez utiliser l'un, l'autre, ou les deux ensemble.
- Des filtres texte par colonne permettent de rechercher par nom de file ou de poste.

### Vues par période et comparaison

Sur une longue période, un tableau jour par jour est difficile à lire. Le sélecteur **Période
totale · Jour · Semaine · Mois** au-dessus du tableau choisit le découpage :

- **Période totale** — une ligne par file (ou file+poste) pour toute la période.
- **Jour** — une ligne par jour (périodes de plus d'un jour).
- **Semaine** — une ligne par semaine ISO, du lundi au dimanche, libellée comme *S10 · 02/03 –
  08/03* (périodes de plus de 7 jours couvrant au moins deux semaines).
- **Mois** — une ligne par mois civil (périodes de 28 jours ou plus couvrant au moins deux mois).

Les vues sans intérêt pour les dates choisies sont grisées ; si vous raccourcissez la période et
que la vue courante ne convient plus, le rapport revient à la **Période totale**. Dans les vues
Semaine et Mois, les lignes sont regroupées sous un en-tête par période, et le tri d'une colonne
s'applique à l'intérieur de chaque période. La première et la dernière période sont coupées à vos
dates : une telle période *partielle* est marquée d'un astérisque — survolez-le pour voir les jours
couverts.

**Comparer à la période précédente** (activé par défaut pour les semaines et les mois, désactivé
pour les jours, dont les variations sont surtout du bruit) ajoute un petit indicateur d'évolution
sous **Appels**, **Répondus**, **Abandonnés**, **Taux %** et **Durée attente** (attente moyenne) :
▲/▼ avec *+12 %* pour les volumes, *+3.2 pts* pour le taux de réponse, *+8 s* pour l'attente
moyenne. Survolez-le pour voir la valeur de la période précédente.

- **Vert** = mieux (plus de répondus, moins d'abandons, meilleur taux, attente plus courte),
  **rouge** = moins bien, **ambre** = variation notable ni bonne ni mauvaise en soi (par exemple plus
  d'appels).
- Un indicateur en gras et encadré signale une variation **majeure** ; légèrement coloré, une
  variation **notable** ; un petit chiffre gris, une variation ordinaire.
- Quand deux périodes n'ont pas le même nombre de jours (mois partiel, février contre mars), les
  volumes sont **comparés par jour** — l'infobulle l'indique.
- Les petits volumes ne sont pas évalués : une file qui passe de 2 à 5 appels n'est pas signalée.
- La première période n'a rien à quoi se comparer.

Quand la comparaison est active, un panneau **Plus fortes variations** au-dessus du tableau liste
les variations les plus significatives en premier (les majeures, puis les files les plus
chargées) : gravité, période contre période précédente, file (et poste), indicateur, valeur
précédente → actuelle et l'évolution, colorée de la même façon. Il en affiche six — **Tout
afficher** déplie la liste. En vue « files uniquement », seules les variations des files sont
listées.

## KPI

Cartes de synthèse (**Total des appels, Répondus, Non répondus, Taux %, Attente moyenne**) plus un
graphique par file/poste sélectionné, et un graphique **Consolidé** les additionnant tous. Choisissez
une **granularité** : consolidée par heure, consolidée par jour de semaine, ou une évolution par
jour, semaine ou mois. Chaque graphique combine une barre empilée Répondus/Non répondus avec une
courbe de Taux de réponse %, et — lorsque des données de temps d'attente existent — une courbe
distincte de Temps d'attente moyen en dessous.

## Origines

Un graphique en anneau détaillant la provenance des appels : Interne, Externe, une autre file
d'attente, Messagerie vocale, SVI, ou Flux d'appel — ou, en alternative, une répartition par les 10
principaux numéros/postes d'origine. Utile pour comprendre *pourquoi* une file reçoit le volume
qu'elle reçoit.

## Export

Les pages Rapport, Tableau de bord, KPI et Origines disposent chacune d'une action d'export
produisant un fichier Excel (.xlsx) de la vue actuelle, respectant les filtres appliqués (période,
exclusion des heures de fermeture, etc.).

Depuis la page Rapport en vue Jour, Semaine ou Mois, le fichier Excel contient aussi une feuille
**Rapport par jour/semaine/mois** et, si la comparaison est active, une feuille **Comparatif**
listant d'abord les plus fortes variations, colorées comme à l'écran.

## Pages associées

- Pour la même répartition par agent individuel plutôt que par file, voir
  [Mes utilisateurs](05-my-users.md).
- Pour envoyer cette vue par e-mail une fois ou de façon programmée, voir
  [Rapports programmés & partage](11-scheduled-reports-and-sharing.md).
- Définitions des colonnes et KPI : [Glossaire des KPI & rapports](13-kpi-and-report-glossary.md).
