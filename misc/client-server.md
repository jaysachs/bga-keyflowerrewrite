# Client-server interface

## Aspirational principles, goals, assumptions

1. Game logic / rules implemented just once, typically in the server
2. Server must offer "all possible legal moves"; avoid allowing client interactions that are not legal.
3. Minimize the number of back/forth communications. Specifically, prefer to send all legal moves for all pieces in one request, rather than offering "here are pieces with legal moves" and then when a piece is selected, send the legal moves for that piece. This may not be feasible, for example due to excessive payload size.

## Specific interactions

### Player turn to bid or activate or pass

There will at most 10-12 tiles available to bid on. In a 6p game, in winter, there will be in addition 6 * 12 possible tiles to activate. (While an individual village might be larger than 12, it's unlikely to have all villages larger than 12.) So let's say there are 100 possible tiles to interact with. (That's an overestimate, which is good.)

*What does a "set of possible bids" for a tile look like?*

A legal bid / activation consists of
* a worker color
* 0 or more workers from the player's stock
* 0 or already-bid groups that are losing their bid
* matches the existing color of workers on the tile, either bid or activated
* such that when added to the player's existing bid on that tile (if any), becomes the winning bid.
* (note to explore consequneces of boat4a)
The worker color can be "any" (meaning any combination) if this is an activation, the player owns boat4b and there is already a bid and/or activation on the tile
The worker color can be "one" (meaning "any single color") if this is an bid, the player owns boat4a


A question comes up: is the client responsible for adding up the selections to ensure they meet the criteria, or should the server return "bundles" that include each possible collection of workers-from-stock,existing-losing-bids ?

Suppose a player had losing bids on 4 other tiles, all the same color, and they had 4 of that worker color in their stock. Furthermore suppose they needed a minimum bid of 2 workers for a given tile. The set of legal bids for that tile might be something like:

```json
   [
     { "color": "blue", "stock-min": 1, "losing-bid-on-tiles": [201] },
     { "color": "blue", "stock-min": 1, "losing-bid-on-tiles": [202] },
     { "color": "blue", "stock-min": 1, "losing-bid-on-tiles": [203] },
     { "color": "blue", "stock-min": 1, "losing-bid-on-tiles": [204] },
     { "color": "blue", "stock-min": 0, "losing-bid-on-tiles": [201, 202] },
     { "color": "blue", "stock-min": 0, "losing-bid-on-tiles": [201, 203] },
     { "color": "blue", "stock-min": 0, "losing-bid-on-tiles": [201, 204] },
     { "color": "blue", "stock-min": 0, "losing-bid-on-tiles": [202, 203] },
     { "color": "blue", "stock-min": 0, "losing-bid-on-tiles": [202, 204] },
     { "color": "blue", "stock-min": 0, "losing-bid-on-tiles": [203, 204] },
     { "color": "blue", "stock-min": 0, "losing-bid-on-tiles": [201, 202, 203] },
     { "color": "blue", "stock-min": 0, "losing-bid-on-tiles": [201, 202, 204] },
     { "color": "blue", "stock-min": 0, "losing-bid-on-tiles": [202, 203, 204] },
     { "color": "blue", "stock-min": 0, "losing-bid-on-tiles": [201, 202, 203, 204] },
   ]
```

Compressing down the json size by abbreviating field:
```json
   [
     { "c": 2, "s": 2, "b": [] },
     { "c": 2, "s": 1, "b": [201] },
     { "c": 2, "s": 1, "b": [202] },
     { "c": 2, "s": 1, "b": [203] },
     { "c": 2, "s": 1, "b": [204] },
     { "c": 2, "s": 0, "b": [201, 202] },
     { "c": 2, "s": 0, "b": [201, 203] },
     { "c": 2, "s": 0, "b": [201, 204] },
     { "c": 2, "s": 0, "b": [202, 203] },
     { "c": 2, "s": 0, "b": [202, 204] },
     { "c": 2, "s": 0, "b": [203, 204] },
     { "c": 2, "s": 0, "b": [201, 202, 203] },
     { "c": 2, "s": 0, "b": [201, 202, 204] },
     { "c": 2, "s": 0, "b": [201, 203, 204] },
     { "c": 2, "s": 0, "b": [202, 203, 204] },
     { "c": 2, "s": 0, "b": [201, 202, 203, 204] },
   ]
```

which is about 400 bytes. With 12 potential tiles to bid on, that's about 4k payload which is fine.

However, now we need to handle activations. Assuming the payload-per-activatable tile is about the same (400 bytes), and let's say we have 20 activatable tiles, puts us a bit over 10k. That's large but not impossible to deal with.

*A note on activations*

The "possible activation" data sent back from the server needs to include, for each possible activation, which positions (1-6) the activating worker group should take.

*Is this feasible to support?*

These payloads will be private. I believe there is an approximately 72MB max size on the gamelog. That leaves us roughly 7200 player actions if the gamelog only consisted of possible moves. With 6 players, that's 1200 possible-move payloads per player, or 300 payloads per season per player. How many "rounds" is a KF season? 20? 30? So for 6p, there are 180 "turns" per player?

**Need to find out**: do private notifs / replies from `onEnteringState()` impact the gamelog? If not, then "who cares". Need to find this out.

#### Activation costs

For several tiles a player needs to supply a component (resource, skill, worker) to activate it. For resource and skill, these are *independent* of the workers used to pay the activation cost. For the tiles with worker costs, Fair, Peddlar and Store require a specific color worker, and this will be easy to build into the existing option bundles, and will not increase the cardinality of bundles. The Tavern, however, has the greatest freedom (any one worker). We can put those choices into the option bundles, which will potentially quadruple the number options (red/green/blue/yellow). Alternatively, we could make this a client-server interaction point; we'd only have to prune the option bundles to include paying the activation cost. There's no need to do this outside of the Tavern, but it might "feel odd" to have those tiles be a special case. It's possibly they could be handled readily client-side, since the client can "know" if there are no more available workers of an existing color.

**Need to analyze Tavern activation more closely.**

#### An alternative (or two)

Suppose we decide to let the client do the addition arithmetic to determin if a bid or activation is legal. We could send out the list of tiles and the size (and color if not the first bid/activation) of the bid required, and of the activation required. As for assembling the set of possible components for a bid or activation, we could also let the client depend on it's already recorded info (though I am not a fan of parsing span contents as data ... could keep as attributes), or we could also just send those out up front as canonical data. Yes, that introduces a point where client/server might be out of sync, but that's not a new issue. It could still happen, and here we're being explicit about "here's the set of things you should look at when letting the user assemble a bid or activation". One about this latter approach I particularly like is that the server can apply any boat4a/boat4b "secret sauce".

### Moving resources

For each hex with resources, server provides

```map<srcTileId,list<pair<list<resourceIds>,list<pair<destinationTileId,int>>>>```

which maps tiles with resources to the resources on those tiles, and the from tiles to a list of reachable destinations with cost. It would also return "total movement points remaining". After moving a resource (a server interaction), an updated map is returned.

### Placing tiles

Initial idea is that we return "which tiles can be placed where in what orientation". Each time a tile is placed, we hit the server for updated possibilities.

Placement of any tile is a server action, returning a new "set of possible placements". Note that moving a tile just placed will need to obey placement constraints. If you need to place a tile where prior placements make it conflict, those prior placements need to be moved first. Perhaps during placement we permit an "un-place", which moves a tile back to the pending set.