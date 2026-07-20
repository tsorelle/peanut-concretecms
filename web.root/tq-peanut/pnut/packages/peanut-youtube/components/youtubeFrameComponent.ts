namespace PeanutYoutube {
    export class youtubeFrameComponent implements IYouTubeClient{
        // observables
        youtubeUrl = ko.observable('');
        playerElementId = ko.observable('video-frame-1');
        status = ko.observable('');
        ready = ko.observable(false);
        player: YT.Player;
        controller: PeanutYoutube.YTFrameController;

        onStateChange : (event) =>  void = null;
        onReady : (event) =>  void = null;
        options : () => object;

        constructor(params: any) {
            let me = this;

            if (!params) {
                console.error('youtubeFrameComponent: Params not defined in translateComponent');
                return;
            }
            if (params.data) {
                params = params.data;
            }
            if (!params.ytcode) {
                console.error('youtubeFrameComponent: Parameter "ytcode" is required');
                return;
            }

            if (params.id) {
                let id  = ko.isObservable(params.id) ?
                    params.id() : params.id;
                me.playerElementId(id);
            }

            if (params.onready) {
                me.onReady = params.ready;
            }

            if (params.onchange) {
                me.onStateChange = params.onStateChange;
            }

            me.options = (params.options) ?
                params.options :
                me.getOptions;

            let ytcode = ko.isObservable(params.ytcode) ?
                params.ytcode() : params.ytcode;

            let url =
                'https://www.youtube.com/embed/' +
                 ytcode +
                '?rel=0&modestbranding=1' +
                '&enablejsapi=1';

            me.youtubeUrl(url);

            me.controller = PeanutYoutube.YTFrameController.register(me)
        }


        getOptions = () => {
            return {
                events: {
                    'onReady': this.onPlayerReady,
                    'onStateChange': this.onPlayerStateChange
                }
            }
        }

        createPlayers = () => {
            let id = this.playerElementId();
            // let id = 'video-frame-1';
            let player = new YT.Player(id, {
                events: {
                    'onReady': this.onPlayerReady,
                    'onStateChange': this.onPlayerStateChange
                }
            });
            this.player = player;
            return [<IPlayerItem>{
                id: id,
                player: player
            }];
        }

        onPlayerReady = (event) => {
            this.ready(true);
            if (this.onReady) {
                this.onReady(event);
            }
        }

        onPlayerStateChange = (event) => {
            this.status(event.data);
            if (this.onStateChange) {
                this.onStateChange(event);
            }
        }
     }
}