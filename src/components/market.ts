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
        const div =  Html.div({ id: `kf_tile_${mt.id}`, attrs: { kf_tiletype: mt.tiletype } });
        // TODO: add bid information appropriately
        div.innerText = `${mt.tiletype}: ${mt.bids}`;
        return div;
    }
}