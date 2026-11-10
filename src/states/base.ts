import { AnimationManager } from "../bgalibs/bga-animations";
import { KFGameDatas, KFPlayer } from "../kfdata";

export abstract class BaseState {
    constructor(protected bga: Bga<KFPlayer, KFGameDatas>,
        protected readonly animationManager: AnimationManager) {
    }

    public onEnteringState(args: any, isCurrentPlayerActive: boolean) { }

    public onLeavingState(args: any, isCurrentPlayerActive: boolean) { }

    /*
    protected autoConfirmEnabled(): boolean {
        let p = this.bga.userPreferences.get(100);
        if (p == 0) {
            return this.bga.gameui.bRealtime;
        }
        return p == 2;
    }
    */
}
