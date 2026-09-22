# Glossaire des KPI & rapports

Une référence unique pour savoir exactement comment chaque chiffre est calculé. Si un nombre sur une
page ne correspond pas à votre propre décompte manuel, vérifiez d'abord sa formule ici — la plupart
des écarts apparents s'expliquent par un détail dans la façon dont « répondu », « abandonné » ou
« perdu » sont définis.

## KPI entrants principaux (Tableau de bord, Mes files d'attente, Mes utilisateurs)

| KPI | Formule |
|---|---|
| **Total des appels** | Somme de tous les appels entrants sur la période. |
| **Répondus** | Somme des appels effectivement décrochés. |
| **Appels perdus** | Total des appels − Abandonnés − Répondus (jamais négatif). « Perdu » signifie précisément que l'appelant a raccroché en attendant réellement un agent — pas pendant l'étape de pré-décroché/SVI. |
| **Temps d'attente moyen** | Durée totale d'attente ÷ total des appels répondus. |
| **Appels courts** | Appels répondus en moins de 10 secondes — signalés séparément car un nombre élevé peut indiquer soit un service rapide, soit des décrochés/raccrochages accidentels à examiner. |
| **Taux de réponse %** | Répondus ÷ (Total des appels − appels abandonnés en pré-décroché) × 100. Cette exclusion des abandons en pré-décroché signifie que le Taux de réponse reflète la qualité du traitement des appels réellement en attente d'un agent, sans être dilué par les appelants ayant raccroché pendant un menu SVI avant d'atteindre une file. |
| **Meilleure / pire heure** | L'heure avec le Taux de réponse % le plus élevé / le plus bas sur la période. |
| **Heure la plus chargée** | L'heure avec le volume d'appels brut le plus élevé (pas nécessairement la mieux ou la moins bien traitée). |

## KPI sortants

Même structure que les entrants, mais plus simple — il n'y a pas de notion d'« abandon » pour les
appels que vous passez :

| KPI | Formule |
|---|---|
| **Appels perdus (sortants)** | Total des appels sortants − Répondus (jamais négatif). |
| **Taux de réponse % (sortants)** | Répondus ÷ Total des appels sortants × 100 (pas d'exclusion de pré-décroché). |

## KPI de tendance (Tableau de bord → Tendances sur 12 mois)

| KPI | Formule |
|---|---|
| **Taux de réponse aux appels (CAR)** | Appels entrants répondus ÷ Appels entrants × 100, comparé mois après mois. |
| **Temps d'attente moyen (tendance)** | La durée d'attente moyenne du mois telle que rapportée par l'API. |
| **% de tendance** | (Mois en cours − Mois précédent) ÷ Mois précédent × 100. Affiché à 0 % s'il n'y a aucune donnée pour le mois précédent à comparer. |

## KPI de supervision des agents

| KPI | Signification |
|---|---|
| **En ligne** | L'agent est enregistré sur le PBX et disponible. |
| **Absent** | L'agent est enregistré mais marqué absent. |
| **Indisponible** | Tout le reste, y compris non enregistré du tout. |

## Colonnes du tableau de rapport (Rapport Mes files d'attente / Mes utilisateurs)

| Colonne | Signification |
|---|---|
| **Appels** | Total des appels atteignant cette ligne (file, ou file+poste). |
| **Non répondus — Déclinés** | Appels non répondus par un agent. |
| **Non répondus — Abandonnés** | Appels raccrochés par l'appelant avant d'être répondus (le nombre d'abandons en pré-décroché apparaît entre parenthèses s'il est non nul). |
| **Interne / Sollicité** | Appels internes vers une file, ou appels spécifiquement acheminés vers un poste. |
| **Répondus** | Appels décrochés. |
| **Transférés** | Appels transmis ailleurs après avoir été répondus. |
| **Taux %** | Voir Taux de réponse % ci-dessus — coloré en vert ≥80 %, jaune ≥60 %, rouge en dessous (seuils configurables, voir [Paramètres PBX → Onglet Tableau de rapport](10-pbx-settings.md#onglet-tableau-de-rapport)). |
| **Durée conv. / Durée attente** | Durée totale de conversation / durée totale d'attente pour la ligne. |

## « Consolidation » — deux significations différentes, à ne pas confondre

1. **Bouton « Consolider » (lignes)** (Rapport Mes files d'attente, vue files uniquement) — fusionne
   les chiffres de chaque file sélectionnée en une seule ligne combinée, uniquement pour
   l'affichage. Cela ne change en rien la façon dont un appel est compté, seulement le nombre de
   lignes affichées.
2. **Bouton « Consolidation multi-files »** — compte l'appel d'un correspondant **une seule fois**,
   même s'il a traversé plusieurs files avant d'être répondu, au lieu d'une fois par file
   traversée. Cela affecte le décompte réel des appels, pas seulement le regroupement des lignes.

Les deux boutons existent indépendamment sur la même page et peuvent être combinés. Aucun des deux
ne fusionne de données **entre différents hôtes PBX** — chaque rapport est toujours limité à un
seul hôte actif à la fois (changez d'hôte via
[Paramètres PBX → Hôte actif](10-pbx-settings.md#onglet-hôte-actif)).

## « Exclure les heures de fermeture »

Un bouton unique, présent sur presque chaque page de rapport/export, qui filtre les appels
enregistrés pendant les heures de fermeture/hors service configurées de votre PBX — afin qu'une
file ou un utilisateur couvert seulement une partie de la journée ne soit pas pénalisé par un
volume hors horaires qu'il n'était jamais censé traiter.

## Étiquettes des segments de l'Analyseur d'appels

Voir [Analyse des appels](07-call-analysis.md#lire-le-flux-dappel) pour la liste complète (Répondu,
Sonnerie, Manqué, Transféré, En attente, Messagerie vocale, SVI, En file).
