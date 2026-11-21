import { AnimationManager } from "../bgalibs/bga-animations";
import { KFGameDatas, KFPlayer } from "../kfdata";

/** Sent on the document whenever the active players change, for components that follow them. */
export const ACTIVE_PLAYERS_CHANGED = 'kf_active_players_changed';

export function activePlayersChanged(): void {
    document.dispatchEvent(new CustomEvent(ACTIVE_PLAYERS_CHANGED));
}

export abstract class BaseState {
    constructor(protected bga: Bga<KFPlayer, KFGameDatas>,
        protected readonly animationManager: AnimationManager) {
    }

    public onEnteringState(args: any, isCurrentPlayerActive: boolean) { }

    public onLeavingState(args: any, isCurrentPlayerActive: boolean) { }

    /**
     * BGA calls this when the active players change within a state (multiple active player states).
     * A new turn doesn't call it: Game.onUpdateActionButtons reports those.
     */
    public onPlayerActivationChange(args: any, isCurrentPlayerActive: boolean) {
        activePlayersChanged();
    }

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
