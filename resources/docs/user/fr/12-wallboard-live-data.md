# Wallboard & données en direct

Un **Wallboard** est un affichage en direct, actualisé automatiquement, du statut actuel de votre
centre d'appels — conçu pour rester ouvert sur un écran ou une télévision de bureau afin que chacun
dans la pièce puisse voir l'activité des files d'attente en un coup d'œil. Tout se passe depuis
**Paramètres PBX → Onglet Wallboard**.

> Nécessite `expert-statistics.modify` pour construire ou modifier un wallboard — toute personne
> ayant `.view` peut tout de même prévisualiser un wallboard existant. Voir
> [Permissions & accès](02-permissions.md).

## Construire un wallboard

Deux façons d'en créer un :

### Construire avec l'IA

Décrivez ce que vous souhaitez en langage naturel dans la zone de saisie (quelques exemples de
requêtes sont proposés sous forme d'étiquettes à remplissage rapide), cliquez sur **Générer**, et
l'IA propose une mise en page de wallboard. Examinez le résultat, puis **Ignorer** ou
**Enregistrer** pour le conserver.

### Éditeur manuel

Cliquez sur **Nouveau** (ou **Modifier** sur un wallboard existant) et définissez :

- **Nom** et **Description**.
- **Colonnes** — la largeur de la mise en page en nombre de cartes (1–6).
- **Actualisation** — la fréquence d'actualisation, en secondes (2–300).
- **Cartes** — ajoutez des cartes une par une, chacune affichant un **Indicateur** choisi (voir le
  tableau ci-dessous), éventuellement filtré sur une file d'attente spécifique. Retirez une carte
  avec son propre contrôle.

Cliquez sur **Enregistrer** une fois terminé.

### Indicateurs disponibles

| Indicateur | Signification |
|---|---|
| Appels en attente | Appels actuellement en file, pas encore répondus. |
| Appels en cours | Appels actuellement traités. |
| Agents occupés / Agents disponibles | Effectif d'agents en direct par état. |
| Attente la plus longue | La plus longue attente actuelle d'un appelant. |
| Appel le plus long | L'appel en cours le plus long actuellement. |
| Total des appels / Appels répondus / Appels abandonnés | Compteurs cumulés pour la période affichée. |
| Aucun agent | Durée ou nombre avec zéro agent disponible. |
| Temps de conversation | Temps de conversation cumulé. |
| Attente moy. / Attente max | Temps d'attente moyen et maximal. |
| Temps de traitement moy. | Durée totale moyenne de traitement par appel. |
| Niveau de service | Pourcentage d'appels répondus dans le délai cible. |
| Taux d'abandon | Pourcentage d'appels abandonnés par l'appelant. |
| Niveau d'alerte de la file | Un indicateur de santé vert/orange/rouge pour la file. |

## Gérer les wallboards existants

La liste des wallboards affiche le nom de chacun (avec des badges « Par défaut » ou « IA » le cas
échéant), un statut Actif/Inactif basculable, et des actions par ligne :

- **Aperçu** — le visualiser sans quitter la page de paramètres.
- **Copier le lien** — copie l'URL publique dans le presse-papiers.
- **Ouvrir** — ouvre cette URL publique dans un nouvel onglet, exactement comme elle apparaîtra sur
  une télévision/un écran.
- **Modifier** (permission de modification requise) — rouvre l'éditeur manuel.
- **Rétablir** (wallboard par défaut uniquement) ou **Supprimer** (autres wallboards) — restaure la
  mise en page par défaut, ou supprime un wallboard personnalisé.

Un aperçu en direct du wallboard actuellement consulté se trouve en haut de l'onglet, s'actualisant
automatiquement.

## Afficher un wallboard publiquement

Cliquez sur **Copier le lien** ou **Ouvrir** pour obtenir l'URL publique. Ce lien :

- Ne nécessite **aucune connexion** — il est conçu pour être ouvert directement sur le navigateur
  d'un écran/d'une télévision partagée.
- S'actualise automatiquement selon l'intervalle configuré.
- Affiche un message clair « lien inactif » si vous avez basculé le statut de ce wallboard sur
  Inactif, ainsi désactiver un affichage public est aussi simple que de basculer ce commutateur —
  pas besoin de supprimer quoi que ce soit.

## Pages associées

- Présence des agents en direct plutôt qu'indicateurs au niveau des files :
  [Supervision des agents](08-agent-monitoring.md).
- Exigences de permission : [Permissions & accès](02-permissions.md).
