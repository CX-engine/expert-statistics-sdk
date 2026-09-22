# Analyseur d'appels (analyse du détail des appels)

Alors que les autres pages de rapport agrègent de nombreux appels, l'**Analyseur d'appels** vous
permet d'explorer le **parcours complet d'un seul appel**, du début à la fin — exactement ce qui
s'est passé, dans l'ordre.

## Trouver un appel

Utilisez les filtres (période, file d'attente, poste, numéro appelant/DID, **Exclure les heures de
fermeture**) pour réduire une liste d'appels, puis ouvrez-en un pour voir le détail.

## Lire le flux d'appel

Chaque appel est découpé en segments étiquetés, affichés dans l'ordre avec leur durée :

| Segment | Signification |
|---|---|
| **Répondu** | Temps réellement passé à parler à quelqu'un. |
| **Sonnerie** | Temps passé à sonner avant d'être décroché (ou non). |
| **Manqué** | L'appel a sonné sans être décroché. |
| **Transféré** | L'appel a été transmis à un autre poste/file. |
| **En attente** | L'appelant a été mis en attente. |
| **Messagerie vocale** | L'appel est allé sur la messagerie vocale. |
| **SVI** | Temps passé dans un menu automatisé (« tapez 1 pour... ») avant d'atteindre une personne. |
| **En file** | Temps passé en attente dans une file pour un agent disponible. |
| (Transit) | Un bref segment de transit/routage entre d'autres étapes. |

Les durées sont affichées dans un format compact « Xm Ys » afin de voir rapidement, par exemple,
qu'un appel a passé 45 secondes dans le SVI, 2 minutes en file, puis 4 minutes 12 secondes en
conversation.

## Pourquoi c'est utile

L'Analyseur d'appels est l'outil approprié lorsqu'un chiffre de rapport semble incorrect et que vous
voulez comprendre *pourquoi* — par exemple, le temps d'attente moyen d'une file semble élevé, et
l'Analyseur d'appels vous permet de retrouver les appels concernés et de voir exactement où le temps
a été passé (menu SVI trop long ? Mise en attente trop longue après décroché ? Rebond entre
plusieurs files avant d'aboutir ?).

## Export

Vous pouvez exporter les données CDR (détail d'appel) sous-jacentes pour l'ensemble filtré d'appels.

## Pages associées

- Pour des versions agrégées de ces mêmes données, voir [Mes files d'attente](04-my-queues.md) ou
  [Mes utilisateurs](05-my-users.md).
- Pour la signification de « heures de fermeture », voir le
  [Glossaire des KPI & rapports](13-kpi-and-report-glossary.md).
