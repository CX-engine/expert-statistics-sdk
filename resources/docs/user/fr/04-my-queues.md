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
| **Conv. moy.** | Durée moyenne de conversation par appel répondu. |
| **Attente moy.** | Temps moyen d'attente dans la file avant réponse, par appel ayant attendu, **message préenregistré exclu** (les appels ayant attendu ici puis débordé vers une autre file comptent aussi). Pour une ligne poste : temps moyen de sonnerie avant que l'agent ne décroche. |

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

**Repérer les anomalies** (désactivé par défaut — cochez-le quand vous voulez l'analyse) compare
chaque période au **niveau habituel** de sa file, calculé sur les **autres périodes sélectionnées** :
avec 6 mois choisis, mars est comparé aux 5 autres mois. En vue **Jour**, un jour est comparé aux
mêmes jours de la semaine (un lundi aux autres lundis), car le trafic dépend du jour. Avec au moins
trois périodes de référence, le niveau habituel est leur valeur médiane : une seule mauvaise période
ne fausse pas la référence des autres.

Seuls les indicateurs qui comptent pour vos appelants sont évalués : le **taux de réponse**,
l'**attente moyenne** et les **abandons**. Le nombre d'appels n'est pas jugé ; il est indiqué comme
cause probable (« 35 % d'appels de plus que d'habitude »).

- Un panneau **Points d'attention** au-dessus du tableau liste les files et périodes nettement
  **moins bonnes que d'habitude**, une ligne par file et période, les plus importantes en premier
  (les files avant les postes) : par exemple *Taux de réponse 72.0 % (habituellement 88.0 %) ·
  Attente moyenne 1 min 40 s (habituellement 35 s) · 35 % d'appels de plus que d'habitude*. Il en
  affiche cinq — **Tout afficher** déplie la liste. Si rien n'est anormal, il l'indique.
- Dans le tableau, seule une valeur anormale porte une mention sous le chiffre, par exemple
  *▼ habit. 88.0 %* : en **rouge** quand c'est moins bien que d'habitude, en **vert** quand c'est
  mieux. Une mention en gras signale un écart **majeur**. Les valeurs dans la normale n'ont rien.
- Les volumes sont rapportés à la durée de la période (un mois partiel, février contre mars).
- Les petits volumes ne sont pas évalués : une file à 3 appels n'est pas signalée.
- En vue « files uniquement », seules les files sont listées.

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
listant d'abord les points d'attention, puis chaque période avec son niveau habituel, colorée comme
à l'écran.

## Pages associées

- Pour la même répartition par agent individuel plutôt que par file, voir
  [Mes utilisateurs](05-my-users.md).
- Pour envoyer cette vue par e-mail une fois ou de façon programmée, voir
  [Rapports programmés & partage](11-scheduled-reports-and-sharing.md).
- Définitions des colonnes et KPI : [Glossaire des KPI & rapports](13-kpi-and-report-glossary.md).
