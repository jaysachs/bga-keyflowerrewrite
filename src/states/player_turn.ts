import { BaseState } from "./base";

export class PlayerTurnState extends BaseState {

    override onEnteringState(args: {
        // TODO ....
    }, isCurrentPlayerActive: boolean): void {
        console.log("player turn", args);
    }
}