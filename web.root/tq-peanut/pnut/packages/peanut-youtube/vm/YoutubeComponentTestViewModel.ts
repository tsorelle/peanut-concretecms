/// <reference path="../../../../pnut/core/ViewModelBase.ts" />
/// <reference path='../../../../typings/knockout/index.d.ts' />
/// <reference path='../../../../pnut/core/Peanut.d.ts' />

namespace PeanutYoutube {
    interface IVideoListItem {
        id: string;
        ytcode: string;
    }
    export class YoutubeComponentTestViewModel extends Peanut.ViewModelBase {
        // observables
        // playerElementId = 'video-frame-1';
        playerElementId = 'video-frame-1';
        player: YT.Player;
        controller : YTFrameController;

        videos : IVideoListItem[] = [
            {id:'video-frame-1',ytcode:'IGA1XC0NivE'},
            {id:'video-frame-2',ytcode:'-7hkRqAeObY'}
        ];


        init(successFunction?: () => void) {
            let me = this;
            Peanut.logger.write('YoutubeComponentTest Init');
            me.application.loadResources('@pkg/peanut-youtube/YTFrameController.js', () => {
                me.application.registerComponents('@pkg/peanut-youtube/youtube-frame',() => {
                    me.bindDefaultSection();
                    // InitApi must be called after binding
                    YTFrameController.initApi();
                    me.controller = YTFrameController.instance;
                    successFunction();
                });
            });
        }

        pause = (item: IVideoListItem) => {
            let player = this.controller.getPlayer(item.id)
            player.pauseVideo();
        }

        stop = (item: IVideoListItem) => {
            let player = this.controller.getPlayer(item.id)
            player.stopVideo();
        }

        play = (item: IVideoListItem) => {
            let player = this.controller.getPlayer(item.id)
            player.playVideo();
        }

    }
}
