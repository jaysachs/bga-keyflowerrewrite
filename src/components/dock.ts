import { Boat, KFGameDatas, KFPlayer } from "../kfdata";
import { BaseComponent } from "../libs/basecomponent";
import { Html } from "../libs/html";


export class DockComponent extends BaseComponent<any> {

    public constructor(private bga: Bga<KFPlayer, KFGameDatas>) {
        super();
    }

    public setup(): HTMLElement {
        const dock = this.bga.gameui.gamedatas.dock;

        return Html.div({id:'kf_dock'},
            ... dock.map(boat => this.boatDiv(boat))
        );
    }

    private boatDiv(boat: Boat): HTMLElement {
        return Html.div({ id: `kf_tile_${boat.id}`, attrs: { kf_tiletype: boat.boattype } },
            Html.div({text: boat.boattype}),
            Html.div({text: "cargo:" }), ... boat.cargo.map(w => Html.span({text: ` ${w.type}-${w.id}` })),
            Html.div({text: "passengers:" }), ... boat.passengers.map(w => Html.span({text: ` ${w.color}-${w.id}` })),
        );
    }
}