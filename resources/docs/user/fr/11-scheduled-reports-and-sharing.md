# Rapports programmés & partage

Tout rapport ou vue du tableau de bord peut être **envoyé une fois par e-mail** ou **programmé pour
se répéter** — les deux démarrent depuis la même fenêtre **Partager/Programmer**, ouverte via les
boutons « Envoyer le rapport » / « Programmer le rapport » présents sur le tableau de bord et les
pages de rapport.

> Nécessite `expert-statistics.modify` — un utilisateur en lecture seule ne peut ni envoyer ni
> programmer de rapports. Voir [Permissions & accès](02-permissions.md).

## Ce qui se passe réellement lorsque vous « partagez » un rapport

Il n'y a ni lien partageable ni fichier joint à télécharger depuis la fenêtre elle-même — partager
un rapport crée un enregistrement d'envoi côté serveur qui transmet directement le contenu du
rapport aux destinataires choisis par e-mail (la fonctionnalité Wallboard est différente — voir
[Wallboard & données en direct](12-wallboard-live-data.md) pour son lien public partageable).

## Envoi unique vs. programmation

La fenêtre propose deux modes, selon le bouton que vous avez cliqué :

- **Envoyer le rapport** — transmet la vue actuelle par e-mail une fois, immédiatement.
- **Programmer le rapport** — la transmet de façon répétée, selon la fréquence choisie.

Champs communs aux deux modes :

- **Surnom** — un nom facultatif pour le rapport, afin de le reconnaître plus tard.
- **Destinataires** — ajoutez une ou plusieurs adresses e-mail (votre propre adresse est pré-remplie
  au départ).
- **Fréquence** (mode programmation uniquement) — Jour, Semaine, ou Mois.
- **Groupe de ressources** (pages de rapport uniquement, pas le tableau de bord) — limitez
  éventuellement le rapport à un groupe défini dans l'
  [onglet Groupes](10-pbx-settings.md#onglet-groupes) plutôt qu'aux éléments sélectionnés
  individuellement.

La validation affiche une notification de confirmation ; la période et les autres filtres actifs sur
la page au moment de l'ouverture de la fenêtre sont automatiquement repris.

## Gérer les programmations existantes

La page **Rapports programmés** (section Configuration du menu) liste chaque rapport récurrent que
vous avez créé — les rapports « envoyés une fois » n'y apparaissent pas, seuls ceux programmés pour
se répéter. Le tableau affiche le Nom, le Type, les éléments/DID couverts, son Groupe de ressources
(le cas échéant), les Destinataires, la Date de début, et la Fréquence.

- **Modifier** (icône crayon) ouvre un panneau permettant de renommer le rapport, d'ajouter/retirer
  des adresses e-mail de destinataires, de changer son groupe de ressources (ce qui met aussi à jour
  les éléments couverts), et d'ajuster les files/postes/DID/appelants inclus.
- **Supprimer** (icône corbeille) retire la programmation après une confirmation.

Vous ne pouvez pas changer la fréquence d'une programmation ni la convertir entre « envoi unique » et
« répétition » depuis cette page — ces paramètres sont fixés à la création du rapport via la fenêtre
Partager/Programmer ; pour les changer, supprimez l'ancienne programmation et créez-en une nouvelle
avec les paramètres souhaités.

## Pages associées

- D'où vient l'option « Groupe de ressources » :
  [Paramètres PBX → Onglet Groupes](10-pbx-settings.md#onglet-groupes).
- Séparation des permissions consultation/modification : [Permissions & accès](02-permissions.md).
