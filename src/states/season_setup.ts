import { Dock, Market, Season } from "../kfdata";
import { BaseState } from "./base";

export class SeasonSetupState extends BaseState {

    override onEnteringState(args: {
        season: Season;
        startPlayerId: number;
        dock: Dock;
        market: Market;
    }, isCurrentPlayerActive: boolean): void {
        console.log("season setup", args);
    }
}