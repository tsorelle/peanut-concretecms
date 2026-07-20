var PeanutYoutube;
(function (PeanutYoutube) {
    class YoutubeTestViewModel extends Peanut.ViewModelBase {
        constructor() {
            super(...arguments);
            this.youtubeUrl = ko.observable('');
            this.playerElementId = 'video-frame-1';
            this.status = ko.observable('');
            this.ready = false;
            this.youtubeCode = ko.observable('IGA1XC0NivE');
            this.videoOn = ko.observable(false);
            this.songTitle = ko.observable('Home on the Range');
            this.showVideo = () => {
                if (!this.videoModal) {
                    let modalElement = document.getElementById('songPlayer1');
                    modalElement.addEventListener('hidden.bs.modal', this.closeVideo);
                    this.videoModal = new bootstrap.Modal(modalElement);
                }
                this.videoModal.show();
                this.videoOn(true);
            };
            this.closeVideo = () => {
                let player = this.getPlayer();
                let state = player.getPlayerState();
                if (state == YT.PlayerState.PLAYING) {
                    player.pauseVideo();
                }
                this.videoOn(false);
            };
            this.getPlayer = () => {
                if (!this.player) {
                    this.player = this.controller.getPlayer(this.playerElementId);
                }
                return this.player;
            };
            this.createPlayers = () => {
                let id = this.playerElementId;
                this.player = new YT.Player(id, {
                    events: {
                        'onReady': this.onPlayerReady,
                        'onStateChange': this.onPlayerStateChange
                    }
                });
                this.ready = true;
                return [{
                        id: this.playerElementId,
                        player: this.player
                    }];
            };
            this.onPlayerReady = (_event) => {
                this.status('ready');
            };
            this.onPlayerStateChange = (event) => {
                this.status(PeanutYoutube.YTFrameController.getPlayerStatus(event.data));
            };
        }
        init(successFunction) {
            let me = this;
            me.application.loadResources('@pkg/peanut-youtube/YTFrameController.js', () => {
                me.application.registerComponents('@pkg/peanut-youtube/youtube-frame', () => {
                    me.bindDefaultSection();
                    PeanutYoutube.YTFrameController.initApi();
                    me.controller = PeanutYoutube.YTFrameController.instance;
                    successFunction();
                });
            });
        }
    }
    PeanutYoutube.YoutubeTestViewModel = YoutubeTestViewModel;
})(PeanutYoutube || (PeanutYoutube = {}));
//# sourceMappingURL=YoutubeTestViewModel.js.map