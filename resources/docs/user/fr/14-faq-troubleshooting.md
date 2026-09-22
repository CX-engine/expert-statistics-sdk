# FAQ & dépannage

**« Je vois un message disant qu'Expert Statistics n'est pas encore activé pour cet hôte. »**
L'activation est indépendante de vos permissions de consultation/modification — c'est un
interrupteur par hôte (essai ou abonnement payant) que seul un administrateur peut activer. Demandez
à votre administrateur de l'activer depuis la page Activation PBX. Voir
[Permissions & accès](02-permissions.md#lactivation--un-interrupteur-indépendant).

**« Un bouton Enregistrer/Créer/Supprimer est grisé ou absent. »**
Vous avez `expert-statistics.view` (lecture seule) mais pas `expert-statistics.modify`. Tout reste
visible, mais le modifier nécessite la permission d'édition. Demandez-la à votre administrateur si
besoin. Voir [Permissions & accès](02-permissions.md).

**« Je ne vois pas du tout Expert Stats dans le menu. »**
Vous n'avez actuellement aucune permission Expert Statistics. Demandez à votre administrateur de
vous accorder au minimum `expert-statistics.view`.

**« Un chiffre du tableau de rapport ne correspond pas à ce que j'ai compté manuellement. »**
Vérifiez la formule exacte dans le [Glossaire des KPI & rapports](13-kpi-and-report-glossary.md) —
la surprise la plus fréquente est que le Taux de réponse % exclut les abandons en pré-décroché de
son dénominateur, et que les « Appels perdus » excluent spécifiquement les abandons (ils sont
comptés séparément, pas mélangés).

**« Quelle est la différence entre « Consolider » et « Consolidation multi-files » ? »**
Ce sont deux boutons différents sur la page Rapport de Mes files d'attente — l'un fusionne des
*lignes* du tableau, l'autre dédoublonne l'appel d'un même correspondant à travers les files.
Aucun des deux ne fusionne de données entre différents hôtes PBX. Explication complète :
[Glossaire des KPI & rapports](13-kpi-and-report-glossary.md#-consolidation---deux-significations-différentes-à-ne-pas-confondre).

**« Puis-je combiner les données de deux de nos hôtes PBX dans un seul rapport ? »**
Non — chaque rapport interroge toujours un seul hôte « actif » à la fois. Changez d'hôte via
[Paramètres PBX → Hôte actif](10-pbx-settings.md#onglet-hôte-actif). Si vous devez régulièrement
comparer des hôtes, exécutez le même rapport sur chacun et comparez manuellement, ou demandez à
votre administrateur si un second hôte devrait devenir votre hôte actif pour une session.

**« Où est passé mon rapport programmé / comment changer sa fréquence ? »**
Gérez les programmations existantes depuis la page **Rapports programmés**. Vous pouvez modifier les
destinataires, son groupe de ressources, et les éléments couverts — mais pas sa fréquence ni son
paramètre envoi unique/répétition. Pour changer cela, supprimez la programmation et créez-en une
nouvelle via la fenêtre Partager/Programmer. Voir
[Rapports programmés & partage](11-scheduled-reports-and-sharing.md).

**« Le lien de mon wallboard affiche « ce lien est inactif ». »**
Son commutateur Actif/Inactif dans l'onglet Wallboard a été désactivé. Rebasculez-le pour rétablir
le lien public sans avoir à le recréer. Voir
[Wallboard & données en direct](12-wallboard-live-data.md).

**« Le Chat IA m'a donné une réponse qui ne correspond pas à la page Rapport. »**
Les réponses du Chat IA sont fondées sur les mêmes données sous-jacentes que les rapports, mais
toujours pour votre hôte actuellement actif et la période qu'il a déduite de votre question — si un
chiffre semble incorrect, demandez-lui de préciser la période et l'hôte exacts utilisés, ou
comparez directement avec la page Rapport équivalente.

**« Je ne peux pas créer ou modifier des hôtes PBX, ni accéder à l'Activation PBX. »**
C'est normal — ce sont des capacités réservées aux administrateurs, entièrement distinctes des
permissions de consultation/modification d'Expert Statistics. Voir
[Permissions & accès](02-permissions.md#niveau-3--gestion-des-hôtes-pbx-administrateurs-uniquement).
