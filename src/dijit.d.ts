declare namespace dijit {
  class TooltipDialog {
    constructor(args: {id: string, content: string});
    destroy(): void;
  }
  class Popup {}
  namespace popup {
    function open({ popup: TooltipDialog,
        around: HTMLElement,
        closable: boolean }): Popup;
    function close(p: Popup): void;
  }
}
