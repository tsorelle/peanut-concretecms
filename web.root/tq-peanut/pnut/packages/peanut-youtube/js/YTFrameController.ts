namespace PeanutYoutube {
    /** API Loading requirements
     *
     *  ViewModels that contain a YoutUbe IFrame or that load youtubeComponents must load this script during init:
     *      me.application.loadResources('@pkg/peanut-youtube/YTFrameController.js', () => {
     *  At the end of the init routine, right after calling  bindDefaultSection(), this routine must be called:
     *      YTFrameController.initApi();
     *   Client objects (ViewModel or youtubeComponent) must call this routine after this script is loaded:
     *      YTFrameController.register(me.playerElementId, me)
     *   Once initialization is complete, the API calls
     *      onYouTubeIframeAPIReady()
     *      which is defined as a global function in this script.
     *   The result is that a player object is created for each YoutubeComponent or ViewModel that has registered.
     *
     */
    export interface IYouTubeClient {
        createPlayers : () => IPlayerItem[];
        // getPlayer : () => YT.Player;
    }

    export interface IPlayerItem {
        id: string;
        player: YT.Player;
    }

    export class YTFrameController {
        static instance = new YTFrameController();
        clients : IYouTubeClient[] = [];
        players : IPlayerItem[] = [];

        static register(client: IYouTubeClient) {
            let instance = PeanutYoutube.YTFrameController.instance;
            instance.clients.push(client);
            return instance;
        }

        static ready = () => {
            YTFrameController.instance.onApiReady();
        }

        onApiReady = () =>{
            this.clients.forEach((client: IYouTubeClient) => {
                let playerItems = client.createPlayers();
                this.players.splice(this.players.length,0,...playerItems);
                // this.players.push(playerItem);
            })
        }

        getPlayer = (id: string) => {
            let item = this.players.find((item) => {
                return item.id == id;
            });
            if (item) {
                return item.player;
            }
            return null;
        }
/*

        static getPlayer(id: string) {
            return PeanutYoutube.YTFrameController.instance.findPlayer(id);
        }
*/

        static initApi = () => {
             var tag = document.createElement('script');
             tag.id = 'iframe-api';
             tag.src = 'https://www.youtube.com/iframe_api';
             var firstScriptTag = document.getElementsByTagName('script')[0];
             firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
         }

         static getPlayerStatus(stateValue: any) {
            switch (stateValue) {
                case YT.PlayerState.UNSTARTED : return 'ready';
                case YT.PlayerState.ENDED     : return 'ended';
                case YT.PlayerState.PLAYING   : return 'playing';
                case YT.PlayerState.PAUSED    : return 'paused';
                case YT.PlayerState.BUFFERING : return 'buffering';
                case YT.PlayerState.CUED      : return 'cued';
                default: return 'not ready'
            }
         }
    }
}
// global api hook
function onYouTubeIframeAPIReady() {
    PeanutYoutube.YTFrameController.ready();
}

