/// <reference path="../../../../pnut/core/ViewModelBase.ts" />
/// <reference path='../../../../typings/knockout/index.d.ts' />
/// <reference path='../../../../pnut/core/Peanut.d.ts' />
/// <reference path='../../../../typings/youtube/index.d.ts' />


namespace PeanutYoutube {
    export class YoutubeTestViewModel extends Peanut.ViewModelBase implements IYouTubeClient{
        // observables

        youtubeUrl = ko.observable('');
        playerElementId = 'video-frame-1';
        status = ko.observable('');
        ready = false;
        player: YT.Player;
        controller : PeanutYoutube.YTFrameController;
        youtubeCode = ko.observable('IGA1XC0NivE');
        videoOn = ko.observable(false);
        videoModal : any;
        songTitle = ko.observable('Home on the Range');


        init(successFunction?: () => void) {
            let me = this;
            /**
             * See YTFrameController
             */

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

        showVideo  = () => {
            if (!this.videoModal) {
                let modalElement = document.getElementById('songPlayer1');
                modalElement.addEventListener('hidden.bs.modal',this.closeVideo);
                this.videoModal = new bootstrap.Modal(modalElement);
            }
            this.videoModal.show();
            this.videoOn(true);
        }

        closeVideo  = () => {
            let player = this.getPlayer()
            let state = player.getPlayerState();
            if (state == YT.PlayerState.PLAYING) {
                player.pauseVideo();
            }
            this.videoOn(false);
        }


        getPlayer = () => {
            if (!this.player) {
                this.player = this.controller.getPlayer(this.playerElementId);
            }
            return this.player;
        }

        createPlayers = () => {
            let id = this.playerElementId;
            this.player = new YT.Player(id, {
                events: {
                    'onReady': this.onPlayerReady,
                    'onStateChange': this.onPlayerStateChange
                }
            });
            this.ready = true;
            return [<IPlayerItem> {
                id: this.playerElementId,
                player: this.player
            }]
        }

        onPlayerReady = (_event) => {
            this.status('ready');
        }

        onPlayerStateChange = (event) => {
            this.status(YTFrameController.getPlayerStatus(event.data));
        }
    }
}
