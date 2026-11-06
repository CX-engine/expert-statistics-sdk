# Audit IA

Montre l'impact de votre configuration 3CX sur vos appels, et précisément ce qu'il faut modifier
dans 3CX pour améliorer vos statistiques. L'audit est calculé chaque jour à partir de la
configuration 3CX de votre PBX et croisé avec les appels des 30 derniers jours (ou de la période
choisie).

> L'Audit IA fait partie des fonctionnalités IA : elles doivent être activées pour
> votre PBX.

## Score de configuration

Le score estime la **part de vos appels traités sans problème de configuration** : 100 % quand
l'audit n'a rien à recommander. Chaque recommandation le diminue selon :

| Facteur | Signification |
|---|---|
| Impact | Un appel perdu (sans réponse ni message) pèse plus qu'une expérience dégradée (attente longue, menu profond), qui pèse plus qu'une configuration fragile (un seul agent, agent dans trop de files). Les points d'entretien (horaires fragmentés, apps inutilisées) coûtent quelques points fixes. |
| Confiance | Les éléments « À vérifier » peuvent être voulus : ils comptent pour moitié. |
| Appels concernés | La part de vos appels qui passent par l'objet concerné. Un problème qui n'existe que hors horaires, pendant les pauses ou les jours fériés ne compte que pour ces appels. |

Le score est aussi donné **par domaine** : routage, files & agents, horaires, Call Flow Apps.

La **couverture de l'analyse** est la part de vos appels dont le routage a pu être entièrement
analysé. Les touches des SVI ne figurent pas dans la configuration 3CX, et une partie de la logique
d'une Call Flow App peut être calculée pendant l'appel : ces parties sont listées sous
**Non analysé** et ne diminuent pas le score.

Sans statistiques d'appels, le score repose sur la configuration seule (et l'indique).

## Recommandations

Triées par les points que chacune coûte sur le score. Chaque recommandation indique :

- la catégorie : **Erreur probable**, **À vérifier** (confirmez que ce n'est pas voulu avant de
  modifier quoi que ce soit) ou **Optimisation** ;
- ce qui a été constaté, et **ce qu'il faut modifier**, avec l'écran et le réglage de l'admin 3CX ;
- les statistiques mesurées et la preuve (l'élément de configuration ou le chiffre qui le montre).

Filtrez par catégorie, par domaine, ou recherchez une file, un agent ou une SDA. La file, l'agent
ou la SDA d'une recommandation est un lien : ouvrez-le, puis utilisez **Retour à …** au-dessus de
l'onglet pour revenir où vous étiez.

### C'est voulu

Certains comportements sont voulus pour votre standard : un message de fermeture après l'attente,
un menu qui raccroche quand personne ne choisit, des agents partagés entre des files de campagne.
Cliquez sur **C'est voulu** dans la recommandation (avec une note si vous le souhaitez) : elle ne
compte plus dans le score et n'est plus signalée, pour cet audit et les suivants. **Marqués comme
attendus** les liste, et **Signaler à nouveau** en fait revenir une. Les modifier demande le droit
de modifier les réglages d'Expert Statistics.

## Routage

Chaque SDA avec sa première destination, ses appels et son nombre de recommandations. Ouvrez une
SDA pour voir sa fiche de cheminement des appels, à lire de gauche à droite puis de haut en bas :

- en haut, seules flèches : la règle entrante, le premier objet (menu, file ou Call Flow App avec ses
  cas particuliers et les services web appelés) et la décision qu'il prend (horaires, ou états sur
  lesquels la Call Flow App route, avec les horaires écrits dans son script) ;
- une colonne par état ou touche de menu, avec les étapes écrites en texte et les sous-décisions
  en retrait ;
- les destinations 3CX atteintes (files avec stratégie, agents, attente maximale et débordement ;
  menus ; messagerie ; numéros externes) regroupées sur leur propre ligne, sans trait vers les
  colonnes pour qu'aucune liaison ne se croise, et en rouge le repli utilisé quand les services web
  de la Call Flow App échouent ;
- les points de vigilance de cette SDA. **⚠** signale un objet concerné par des recommandations :
  survolez-le, ou un point de vigilance, pour les lire, cliquez pour accéder à la recommandation plus bas.

Quand une Call Flow App choisit ses destinations pendant l'appel, les appels réellement observés
(destinations, nombre d'appels) sont affichés sous les recommandations.

## Files d'attente et agents

- **Files d'attente** : stratégie, agents (connectés lors du relevé de la configuration), durée de
  sonnerie, attente maximale, post-appel, destination si pas de réponse, horaires, avec les appels,
  les taux de réponse et d'abandon et l'attente moyenne de la période.
- **Agents** : chaque agent de file avec ses files, les appels de file présentés et répondus, les
  heures de connexion à chaque file sur la période, et les recommandations qui le concernent
  (agents répondant à peu d'appels, jamais connectés, désactivés, partagés avec une file qui perd
  des appels…). Le statut (profil, connecté ou non) est celui **lors du relevé de la configuration**, pas
  sur la période.

## Historique, rapport et relance

- **Historique** liste les audits précédents et leur score.
- **Rapport** télécharge l'audit complet (routage de chaque SDA et toutes les recommandations) au
  format Markdown.
- **Relancer l'audit** analyse à nouveau la dernière configuration 3CX et recalcule le score.

## Fichiers de l'audit

**Configuration → Paramètres PBX → Fichiers de l'Audit IA** ([Paramètres PBX](10-pbx-settings.md)) montre à partir de quoi l'audit est calculé, et
permet à un administrateur de fournir :

- la **configuration sauvegardée** (fichier .xml de la sauvegarde 3CX), quand les sauvegardes du
  standard ne sont pas collectées : elle est vérifiée, puis l'audit est relancé dessus ;
- les **sources des Call Flows** de chaque Call Flow App (projet Call Flow Designer, .zip), à
  remplacer à chaque modification du Call Flow ;
- les **messages audio** (.zip des fichiers .wav de la sauvegarde) : ils sont transcrits, pour que
  l'audit sache ce qu'entendent les appelants (message de fermeture, menu, invitation à rappeler).

Chaque fichier est vérifié avant d'être conservé ; un fichier refusé indique pourquoi.

## Pages liées

- Le chat IA, qui peut expliquer l'audit et le croiser avec vos statistiques : [Analyses IA](09-ai-insights.md).
- Les chiffres utilisés par l'audit : [Glossaire des KPI et rapports](13-kpi-and-report-glossary.md).
