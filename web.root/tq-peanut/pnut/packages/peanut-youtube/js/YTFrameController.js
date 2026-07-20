var PeanutYoutube;
(function (PeanutYoutube) {
    class YTFrameController {
        constructor() {
            this.clients = [];
            this.players = [];
            this.onApiReady = () => {
                this.clients.forEach((client) => {
                    let playerItems = client.createPlayers();
                    this.players.splice(this.players.length, 0, ...playerItems);
                });
            };
            this.getPlayer = (id) => {
                let item = this.players.find((item) => {
                    return item.id == id;
                });
                if (item) {
                    return item.player;
                }
                return null;
            };
        }
        static register(client) {
            let instance = PeanutYoutube.YTFrameController.instance;
            instance.clients.push(client);
            return instance;
        }
        static getPlayerStatus(stateValue) {
            switch (stateValue) {
                case YT.PlayerState.UNSTARTED: return 'ready';
                case YT.PlayerState.ENDED: return 'ended';
                case YT.PlayerState.PLAYING: return 'playing';
                case YT.PlayerState.PAUSED: return 'paused';
                case YT.PlayerState.BUFFERING: return 'buffering';
                case YT.PlayerState.CUED: return 'cued';
                default: return 'not ready';
            }
        }
    }
    YTFrameController.instance = new YTFrameController();
    YTFrameController.ready = () => {
        YTFrameController.instance.onApiReady();
    };
    YTFrameController.initApi = () => {
        var tag = document.createElement('script');
        tag.id = 'iframe-api';
        tag.src = 'https://www.youtube.com/iframe_api';
        var firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
    };
    PeanutYoutube.YTFrameController = YTFrameController;
})(PeanutYoutube || (PeanutYoutube = {}));
function onYouTubeIframeAPIReady() {
    PeanutYoutube.YTFrameController.ready();
}
//# sourceMappingURL=YTFrameController.js.map