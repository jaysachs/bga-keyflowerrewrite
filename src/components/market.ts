import { KFGameDatas, KFPlayer, MarketTile } from "../kfdata";
import { BaseComponent } from "../libs/basecomponent";
import { Html } from "../libs/html";


export class MarketComponent extends BaseComponent<any> {

    public constructor(private bga: Bga<KFPlayer, KFGameDatas>) {
        super();
    }

    public setup(): HTMLElement {
        const mkt = this.bga.gameui.gamedatas.market;

        return Html.div({id:'kf_market'},
            ... mkt.map(mt => this.marketTileDiv(mt))
        );
    }

    private marketTileDiv(mt: MarketTile): HTMLElement {
        return Html.div({ id: `kf_tile_${mt.id}`, attrs: { kf_tiletype: mt.tiletype } },
            Html.span({text:`${mt.tiletype}-${mt.id}: `}),
            ... mt.bids.map(b => Html.span({text: `${b.player_id}: ${b.workers.map(w => w.id + ":" + w.color)}`}))
        );
    }
}