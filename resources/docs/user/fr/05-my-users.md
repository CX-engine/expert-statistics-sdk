# Mes utilisateurs

Le même niveau de détail que [Mes files d'attente](04-my-queues.md), mais centré sur les
utilisateurs individuels (postes/agents) plutôt que sur les files d'attente. Quatre sous-pages :
**Tableau de bord**, **Rapport**, **KPI**, et **Origines**, suivant toutes le même principe — choisir
d'abord vos utilisateurs et une période.

## Rapport

La page Rapport comporte trois onglets :

### Onglet Appels

Les colonnes sont regroupées par sens d'appel — **Sortants**, **Entrants**, **Internes**,
**Total** — et dans chaque groupe vous trouverez :

- **Durée (totale / moyenne)** — durée totale, et durée moyenne par appel.
- **Non répondus** — appels que cet utilisateur n'a pas décrochés.
- **Attente (moyenne)** — pertinent uniquement pour les entrants : combien de temps les appelants
  ont attendu avant que cet utilisateur ne réponde.
- **Nombre** — nombre d'appels.

Survolez un en-tête de colonne pour une infobulle expliquant précisément ce qu'elle compte — par
exemple, « Taux de réponse entrant » ou « Durée d'attente moyenne » sont précisés sans ambiguïté
entre les colonnes aux noms similaires des groupes Sortants/Entrants/Internes.

### Onglet Statut

Deux groupes :

- **Connexions** — Heure de connexion, Heure de déconnexion, Durée totale de connexion sur la
  période.
- **Répartition du temps** — comment le temps connecté de l'utilisateur s'est réparti entre
  **Disponible**, **Absent**, **Ne pas déranger**, **Personnalisé 1**, **Personnalisé 2** (votre
  administrateur peut renommer ces deux statuts personnalisés — voir
  [Paramètres PBX → Onglet Agent](10-pbx-settings.md#onglet-agent)), et **Disponible hors appel** —
  ce dernier correspond au temps en statut Disponible *moins* le temps passé en appel, c'est-à-dire
  le temps réellement inactif et prêt à répondre.

### Onglet Files d'attente

De quelles files d'attente cet utilisateur a pris des appels pendant la période.

## Tableau de bord, KPI, Origines

Structurellement identiques aux pages équivalentes de [Mes files d'attente](04-my-queues.md), mais
centrées sur les utilisateurs plutôt que sur les files — mêmes cartes en jauge radiale, mêmes
options de granularité KPI (heure / jour de semaine / jour / semaine / mois), même graphique en
anneau des origines d'appel.

## Options communes

- **Exclure les heures de fermeture** — disponible sur chaque sous-page de Mes utilisateurs, même
  comportement que dans Mes files d'attente.
- L'export Excel est disponible sur chaque sous-page.

## Pages associées

- Équivalent au niveau des files d'attente : [Mes files d'attente](04-my-queues.md).
- Présence des agents en direct plutôt que rapport historique :
  [Supervision des agents](08-agent-monitoring.md).
- Définitions des colonnes : [Glossaire des KPI & rapports](13-kpi-and-report-glossary.md).
