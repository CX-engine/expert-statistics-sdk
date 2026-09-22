# Analyses IA

Trois pages propulsées par l'IA, toutes fondées sur les données réelles d'appels/files/agents de
votre hôte PBX — pas sur des connaissances génériques de chatbot.

> Remarque : ce module « Analyses IA » (Chat / Tableau de bord / Alertes) répond aux **questions sur
> vos données d'appels** (« combien d'appels avons-nous perdus la semaine dernière ? »). C'est une
> fonctionnalité différente de l'assistant documentaire décrit dans ce panneau d'aide, qui répond
> aux questions sur **la façon d'utiliser Expert Statistics lui-même**.

## Chat IA

Un assistant conversationnel pour vos statistiques. Posez une question en langage naturel et
obtenez une réponse fondée sur les données de votre hôte — les réponses peuvent inclure du texte
simple, des cartes de KPI, ou des tableaux complets, ainsi que des suggestions de questions de suivi
cliquables pour approfondir sans retaper votre question.

- Des **suggestions de démarrage** apparaissent lorsque vous ouvrez une nouvelle conversation, si
  vous ne savez pas quoi demander.
- **Historique** — chaque conversation est enregistrée ; la barre latérale gauche liste les
  conversations passées par titre, heure, et nombre de messages, et vous pouvez rouvrir n'importe
  laquelle d'entre elles.
- **Nouvelle conversation** — le bouton « + » démarre un nouveau fil.
- **Effacer l'historique** — supprime toutes les conversations enregistrées (confirmation requise).
- D'autres pages (comme les cartes « action rapide » du Tableau de bord IA) peuvent créer un lien
  direct vers une question pré-remplie ici.

## Tableau de bord IA

Une grille de cartes d'analyse générées par IA, actualisées périodiquement ou à la demande :

| Type de carte | Ce qu'elle montre |
|---|---|
| Résumé d'alertes | Une synthèse des conditions d'alerte actuelles. |
| Tendance / instantané KPI | Un indicateur avec son pourcentage de variation à la hausse/baisse. |
| Modèle | Des schémas reconnus, par ex. un comportement récurrent aux heures de pointe. |
| Recommandation | Des suggestions numérotées et actionnables, avec leur justification. |
| Suggestion | Observations par agent ou par file d'attente. |
| Action rapide | Un raccourci cliquable qui vous amène dans le Chat IA avec une question pertinente pré-remplie. |

Chaque carte est colorée selon sa gravité (critique / avertissement / info). Les cartes non lues
affichent un indicateur pulsant ; cliquez sur une carte pour la marquer comme lue, ou utilisez
**Tout marquer comme lu**. Chaque carte peut être fermée individuellement. Un bouton **Actualiser**
force l'IA à régénérer immédiatement les analyses plutôt que d'attendre la prochaine actualisation
programmée ; si vous n'avez encore jamais généré d'analyses, utilisez le bouton **Générer des
analyses** de l'état vide.

## Alertes IA

Les alertes déjà déclenchées par le système, regroupées en : **Performance des files**, **Heures de
pointe**, **Volume**, et **Performance des agents**, avec un compteur par catégorie et des cartes de
synthèse pour les totaux critique/avertissement/info. Développez une alerte pour voir sa
description, une barre de progression pour les indicateurs de type taux, et une recommandation.
Utilisez **Vérifier maintenant** pour forcer une vérification immédiate plutôt que d'attendre la
prochaine programmée, ou **Ignorer** pour effacer une alerte individuelle.

### Configurer ce qui déclenche une alerte

Les *seuils* d'alerte ne se configurent pas ici — ils se trouvent dans l'**onglet Alertes IA des
Paramètres PBX** (voir [Paramètres PBX](10-pbx-settings.md#onglet-alertes-ia)), où vous contrôlez si
les alertes sont activées, leur fréquence de vérification, la langue/e-mail de notification, et les
seuils précis d'avertissement/critique pour le taux d'abandon, le taux de non-réponse, l'abandon en
pré-décroché, le temps d'attente, et la sensibilité de changement de volume. Modifier les seuils
nécessite `expert-statistics.modify` — voir [Permissions & accès](02-permissions.md).

## Pages associées

- Configuration des seuils : [Paramètres PBX → Onglet Alertes IA](10-pbx-settings.md#onglet-alertes-ia).
- Les chiffres derrière une réponse de l'IA :
  [Glossaire des KPI & rapports](13-kpi-and-report-glossary.md).
