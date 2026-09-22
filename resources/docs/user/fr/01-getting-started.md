# Premiers pas avec Expert Statistics

Expert Statistics transforme les données brutes de votre système téléphonique en tableaux de bord,
rapports, wallboards en direct et analyses générées par IA, afin que vous puissiez voir comment vos
files d'attente, utilisateurs et numéros fonctionnent réellement — sans exporter de tableurs ni
demander un rapport à votre service informatique.

## Ce qu'il vous faut avant de commencer

1. **Un hôte PBX connecté à votre compte.** Expert Statistics lit les données d'un seul hôte PBX à
   la fois. Si votre organisation possède plusieurs hôtes, vous choisissez celui qui est « actif »
   depuis **Configuration → Paramètres PBX → Hôte actif** (voir
   [Paramètres PBX](10-pbx-settings.md)).
2. **Expert Statistics activé pour cet hôte.** L'activation (essai gratuit ou abonnement payant) est
   quelque chose qu'un administrateur active par hôte — voir
   [Permissions & accès](02-permissions.md#lactivation--un-interrupteur-indépendant). Si vous ouvrez
   une page de rapport et voyez *« Expert Statistics n'est pas encore activé pour cet hôte »*, c'est
   ce qui manque — demandez à un administrateur de l'activer.
3. **La permission de consulter ou de modifier Expert Statistics**, accordée par votre
   administrateur. Voir [Permissions & accès](02-permissions.md) pour savoir précisément ce que
   chaque niveau vous permet de faire.

## Où trouver chaque fonctionnalité

Une fois configuré, ouvrez **Expert stats** dans le menu latéral principal. Chaque page d'Expert
Statistics partage la même sous-navigation à gauche :

| Section | À quoi ça sert |
|---|---|
| **Accueil** | Page d'atterrissage du module. |
| **Tableau de bord** | La vue d'ensemble : totaux, tendances, cartes d'analyse, actions rapides. Voir [Tableau de bord](03-dashboard.md). |
| **Mes numéros** | Rapports sur vos numéros DID (numéros entrants). Voir [Mes numéros & numéros appelants](06-my-numbers-caller-numbers.md). |
| **Numéros appelants** | Rapports sur les numéros *qui vous appellent*. Même document que ci-dessus. |
| **Mes files d'attente** (Tableau de bord / Rapport / KPI / Origines) | Tout sur les files d'attente d'appels. Voir [Mes files d'attente](04-my-queues.md). |
| **Mes utilisateurs** (Tableau de bord / Rapport / KPI / Origines) | Tout sur les postes/agents individuels. Voir [Mes utilisateurs](05-my-users.md). |
| **Agents** (Statut en direct / Connexion aux files / Répartition des statuts) | Présence des agents en direct et historique. Voir [Supervision des agents](08-agent-monitoring.md). |
| **Analyseur d'appels** | Explorez le parcours complet d'un appel (sonnerie, attente, transfert, messagerie...). Voir [Analyse des appels](07-call-analysis.md). |
| **Analyses IA** (Chat / Tableau de bord / Alertes) | Posez des questions en langage naturel et obtenez des analyses générées par IA. Voir [Analyses IA](09-ai-insights.md). |

En dehors de cette sous-navigation, dans le menu latéral principal sous **Configuration**, vous
trouverez également :

- **Paramètres PBX** — hôte actif, libellés de statut d'agent, temporisation de pré-décroché des
  files d'attente, seuils du tableau de rapport, seuils d'alertes IA, groupes de ressources, et
  wallboards. Voir [Paramètres PBX](10-pbx-settings.md).
- **Activation PBX** — réservé aux administrateurs ; c'est ainsi qu'Expert Statistics est activé
  pour un hôte au départ (voir le guide revendeur/administrateur séparé si vous êtes
  administrateur).
- **Rapports programmés** — gérez les envois récurrents de rapports déjà configurés. Voir
  [Rapports programmés & partage](11-scheduled-reports-and-sharing.md).

## Une première visite rapide

1. Ouvrez **Expert stats → Tableau de bord**. C'est le moyen le plus rapide d'obtenir une vue
   d'ensemble : total des appels, taux de réponse, appels perdus, et tendances sur 12 mois.
2. Choisissez une période dans le sélecteur (Aujourd'hui, Hier, Cette semaine, Ce mois, etc.).
3. Si un chiffre vous semble étrange, cliquez sur **Mes files d'attente → Rapport** ou
   **Mes utilisateurs → Rapport** pour voir le détail, ou ouvrez l'**Analyseur d'appels** pour
   examiner des appels individuels.
4. Utilisez les boutons **Envoyer le rapport** / **Programmer le rapport** du tableau de bord si
   vous souhaitez que cette vue soit envoyée par e-mail, une fois ou de façon récurrente.

## Pour aller plus loin

- Nouveau sur le modèle de permissions ? Commencez par [Permissions & accès](02-permissions.md).
- Vous voulez comprendre un chiffre précis (Taux de réponse, SLA, Appels perdus...) ? Voir le
  [Glossaire des KPI & rapports](13-kpi-and-report-glossary.md).
- Quelque chose ne se comporte pas comme prévu ? Consultez la
  [FAQ & dépannage](14-faq-troubleshooting.md).
