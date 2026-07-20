var PeanutYoutube;
(function (PeanutYoutube) {
    class YoutubeComponentTestViewModel extends Peanut.ViewModelBase {
        constructor() {
            super(...arguments);
            this.playerElementId = 'video-frame-1';
            this.videos = [
                { id: 'video-frame-1', ytcode: 'IGA1XC0NivE' },
                { id: 'video-frame-2', ytcode: '-7hkRqAeObY' }
            ];
            this.pause = (item) => {
                let player = this.controller.getPlayer(item.id);
                player.pauseVideo();
            };
            this.stop = (item) => {
                let player = this.controller.getPlayer(item.id);
                player.stopVideo();
            };
            this.play = (item) => {
                let player = this.controller.getPlayer(item.id);
                player.playVideo();
            };
        }
        init(successFunction) {
            let me = this;
            Peanut.logger.write('YoutubeComponentTest Init');
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
    PeanutYoutube.YoutubeComponentTestViewModel = YoutubeComponentTestViewModel;
})(PeanutYoutube || (PeanutYoutube = {}));
//# sourceMappingURL=YoutubeComponentTestViewModel.js.map