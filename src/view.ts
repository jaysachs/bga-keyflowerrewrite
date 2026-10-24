export class IDS {
  static worker(locationId: string, playerId: number): string {
    return `worker_${locationId}_${playerId}`;
  }

  static playerBoard(playerId: number): string {
    return `player_board_${playerId}`;
  }

  static playerArea(playerId: number): string {
    return `player_area_${playerId}`;
  }

  static villageTile(playerId: number, x: number, y: number): string {
    return `village_${playerId}_${x}_${y}`;
  }
}

export class View {

  static targetId(evt: Event): string {
    return (evt.currentTarget as HTMLElement).id;
  }


  static worker(args: {type: number, id: string, num: number | ""}): string {
    return `<div class="worker_hand worker${args.type}" id="worker_${args.id}">${args.num}</div>`
  }

  static worker_elem(args: {type: number, id?: string, num?: (number | "")}): HTMLElement {
    const elem = document.createElement('div');
    if (args.id) { elem.id = "worker_" + args.id; }
    elem.classList.add("worker_hand", `worker${args.type}`);
    if (args.num !== undefined) { elem.innerText = `${args.num}`; }
    return elem;
  }

  static skill_elem(args: { type: number, id?: string, num?: number | ""}): HTMLElement {
    const elem = document.createElement('div');
    if (args.id) { elem.id = "skill_" + args.id; }
    elem.classList.add("skill_hand", `skill${args.type}`);
    if (args.num !== undefined) { elem.innerText = `${args.num}`; }
    return elem;
  }

  static marketLot(id: string): string {
    return `<div class="marketLot" id="marketLot_${id}"></div>`
  }
  static biddingBox(args: {bid: number, mid: string, pid: number}): string {
    return `<div class="biddingBox biddingBox${args.bid}" id="biddingBox_${args.mid}_${args.pid}"></div>`
  }
  static biddingGlow(args: {bid: number, mid: string, pid: number, color: string}): string {
    return `<div class="biddingGlow biddingGlow${args.bid}" id="biddingGlow_${args.mid}_${args.pid}"><div class="biddingGlowCropContainer"><div class="biddingGlowShadow" style="box-shadow: 0px 0px 10px 4px #${args.color};"></div></div></div>`
  }
  static biddingButton(mid: string): string {
    const LABEL_BID = _("Bid");
    return `<div class="action-button bgabutton bgabutton_blue bidbutton" id="bidButton_${mid}">${LABEL_BID}</div>`;
  }
  static actionBox(tid: string): string {
    return `<div class="actionBox" id="actionBox_${tid}"></div>`
  }
  static actionSub(tid: string, aid: number): string {
    return `<div class="actionSub" id="actionSub_${tid}_${aid}"></div>`
  }
  static villagetile(args: {
    left: number,
    top: number,
    pid: number,
    x: number,
    y: number,
  }): string {
    return `<div class="village" style="left: ${args.left}px; top: ${args.top}px;" id="${IDS.villageTile(args.pid,args.x,args.y)}"></div>`;
  }

  static tile(args: {
    // FIXME: this is smelly
    type: string | number,
    id: string | number,
    side: number,
    orient: number,
    scored: string,
  }): string {
    return `
    <div class="tile tile_${args.id}_${args.side} tile${args.type} scored_${args.scored}"  id="tile_${args.id}">
      <div id="tile_background_${args.id}" class="tile_background orientation${args.orient} tile_${args.id}_${args.side}"></div>
      <div class="scored_wrap">
        <div id="scored_${args.id}" class="scored">0</div>
      </div>
    </div>`;
  }

  static tileBox(mid: number): string {
    return`<div class="tileBox" id="tileBox_${mid}"></div>`
  }

  static tile_select(id: number | string) : string {
    return `<div class="tile tile_select" id="tile_select_${id}"></div>`
  }

  static boatZone(bid: string): string {
    return `<div id="boatZone_${bid}" class="boatZone"></div>`
  }

  static resourceZone(tid: string): string {
    return `<div id="resourceZone_${tid}" class="resourceZone"></div>`
  }

  static worker_mini_elem(args: { type: string, id: string | number, classes?: string[] }): HTMLElement {
    const elem = document.createElement('div');
    elem.id = `worker_mini_${args.id}`;
    elem.classList.add("worker_mini", `worker_mini${args.type}`, ...(args.classes ?? []));
    return elem;
  }

  static skill_mini(args: {type: string, id: string | number}): string {
    return `<div class="skill_mini skill_mini${args.type}" id="skill_mini_${args.id}"></div>`
  }

  static skill_mini_elem(args: {type: string, id: string | number}): HTMLElement {
    const elem = document.createElement('div');
    elem.id = `skill_mini_${args.id}`;
    elem.classList.add('skill_mini', `skill_mini${args.type}`);
    return elem;
  }

  static resource_mini_elem(args:{type: string, id?: string}): HTMLElement {
    const elem = document.createElement('div');
    if (args.id) { elem.id = `resource_mini_${args.id}`; }
    elem.classList.add('resource_mini', `resource_mini${args.type}`);
    return elem;
  }

  static resource_elem(args:{type: string, id?: string}): HTMLElement {
    const elem = document.createElement('div');
    if (args.id) { elem.id = `resource_${args.id}`; }
    elem.classList.add('resource', `resource${args.type}`);
    return elem;
  }

  static worker_panel(title: string): string {
    return `<div id="worker_panel"><h3>${title}</h3><div class="worker_line" id="worker_line_1"></div><div class="worker_line" id="worker_line_2"></div><div class="worker_line" id="worker_line_3"></div><div class="worker_line" id="worker_line_4"></div><div class="worker_line" id="workers_starter"></div></div>`
  }
}
