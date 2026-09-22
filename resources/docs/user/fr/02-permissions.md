# Permissions & accès

Expert Statistics utilise deux niveaux de contrôle d'accès indépendants. Comprendre la différence
évite beaucoup de confusion sur « pourquoi puis-je voir ceci mais pas le modifier ? ».

## Niveau 1 — Consultation vs. Modification (celui qui vous concerne au quotidien)

Votre administrateur vous accorde l'une de ces permissions :

| Permission | Ce qu'elle vous permet de faire |
|---|---|
| **`expert-statistics.view`** (lecture seule) | Parcourir **toutes** les pages d'Expert Statistics — Tableau de bord, Mes files d'attente, Mes utilisateurs, Mes numéros, Numéros appelants, Supervision des agents, Analyseur d'appels, Analyses IA, Rapports programmés, et tous les onglets des Paramètres PBX. Vous pouvez filtrer, changer les périodes, changer d'hôte, et tout consulter. |
| **`expert-statistics.modify`** (lecture + écriture) | Tout ce que `.view` permet, **plus** la possibilité d'enregistrer des modifications : les onglets des Paramètres PBX (libellés d'agent, temporisation de pré-décroché des files d'attente, seuils du tableau de rapport, seuils d'alertes IA), créer/modifier/supprimer des **Groupes** de ressources, construire/modifier des **Wallboards**, et modifier/supprimer des **Rapports programmés**. |

Une permission raccourcie, `expert-statistics.*`, accorde les deux à la fois.

**Le point important : lecture seule ne veut pas dire « impossible de voir la configuration ».** Si
vous n'avez que `expert-statistics.view`, vous pouvez tout de même ouvrir les Paramètres PBX et
consulter chaque onglet — vous verrez une bannière « lecture seule », et chaque bouton
Enregistrer/Créer/Supprimer sera désactivé ou masqué. Il faut `.modify` pour réellement changer
quelque chose.

Cette séparation s'applique à :

- Chaque onglet des **Paramètres PBX** (Hôte actif, Agent, File d'attente, Tableau de rapport,
  Alertes IA, Groupes, Wallboard) — consultable par toute personne ayant `.view`, modifiable
  uniquement avec `.modify`.
- **Rapports programmés** — la liste est consultable par toute personne ayant `.view` ; modifier ou
  supprimer une programmation existante nécessite `.modify`.
- **Envoyer ou programmer un nouveau rapport** depuis le tableau de bord/les pages de rapport (la
  fenêtre de partage/programmation) nécessite également `.modify`.

Si un bouton semble grisé ou absent et que vous pensez devoir y avoir accès, demandez à votre
administrateur de vous accorder `expert-statistics.modify`.

## L'activation : un interrupteur indépendant

Même avec la permission complète `expert-statistics.modify`, aucune page de rapport ne fonctionnera
tant qu'un **administrateur n'a pas activé Expert Statistics pour votre hôte PBX** — soit en essai
gratuit, soit en abonnement payant. C'est un mécanisme entièrement distinct des permissions
consultation/modification ci-dessus ; il se gère depuis la page **Activation PBX**, accessible aux
seuls administrateurs.

Si un hôte n'est pas activé, chaque page de rapport (à l'exception du contrôle d'accès propre au
Tableau de bord, qui utilise un mécanisme équivalent) affiche un message de ce type :

> *Expert Statistics n'est pas encore activé pour cet hôte. Améliorez la satisfaction client et la
> productivité de votre équipe en analysant vos appels par file d'attente, utilisateur ou numéro...
> Demandez à un administrateur d'activer Expert Statistics pour cet hôte, ou contactez-nous pour
> l'activer.*

Si vous voyez ce message, il n'y a rien à configurer de votre côté — contactez votre administrateur.
(Si vous *êtes* l'administrateur, consultez le guide revendeur/administrateur séparé pour savoir
comment activer un hôte.)

## Niveau 3 — Gestion des hôtes PBX (administrateurs uniquement)

Créer, modifier ou supprimer les hôtes PBX eux-mêmes (détails de connexion, identifiants,
informations de licence) est une troisième capacité, entièrement distincte et réservée aux
administrateurs, sans lien avec `expert-statistics.*`. Elle est couverte dans le guide
revendeur/administrateur, pas ici — en tant qu'utilisateur standard, vous n'en aurez jamais besoin.

## Référence rapide

| Question | Réponse |
|---|---|
| « Je vois une page mais chaque bouton Enregistrer est désactivé. » | Vous avez `.view` mais pas `.modify`. Demandez à un administrateur. |
| « J'obtiens un message 402 / « pas encore activé ». » | L'hôte n'est pas activé. Demandez à un administrateur de l'activer. |
| « Je ne vois pas du tout Expert Stats dans le menu. » | Vous n'avez pas `expert-statistics.view` (ni `.modify`/`.*`). Demandez à un administrateur. |
| « Je ne peux pas créer ou modifier des hôtes PBX. » | C'est normal — la gestion des hôtes est une capacité réservée aux administrateurs, distincte des permissions Expert Statistics. |
