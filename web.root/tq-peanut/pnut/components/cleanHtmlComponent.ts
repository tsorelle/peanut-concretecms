/**
 * Created by Terry on 6/10/2019
 * Prerequisites:
 * Using TinyMCE
 * TinyMCE init includes.
 *   plugins: (must include "paste"),
 *	 paste_word_valid_elements: "b,strong,i,em,h1,h2,h3,p,a,ul,li"
 */
/// <reference path='../../typings/knockout/index.d.ts' />
/// <reference path='../../typings/tinymce/tinymce.d.ts' />
/// <reference path='../core/KnockoutHelper.ts' />
namespace Peanut {
    export class cleanHtmlComponent {
        public bootstrapVersion : KnockoutObservable<number>;
        private translator : ITranslator = null;
        private editor: any;
        private editorId: string;
        private content: string;

        removeBlanks = ko.observable(true);
        convertHeadings = ko.observable('h2');
        cleaning = ko.observable(false);

        constructor(params : any) {
            let me = this;

            if (!params) {
                console.error('Params not defined in cleanHtmlComponent');
                throw('Cannot initialize cleanHtmlComponent');
            }
            if (!params.editorId) {
                console.error('cleanupHtml component requires editorId parameter giving ID of TinyMCE target text area.')
                throw('Cannot initialize cleanHtmlComponent');
            }

            me.editorId = params.editorId;

            if (params.translator) {
                // todo: implement translations
                // me.translator = params.translator();
            }

            me.bootstrapVersion = ko.observable(3);
            PeanutLoader.loadUiHelper(() => {
                me.bootstrapVersion(Peanut.ui.helper.getVersion());
            });

        }

        showModal = () => {
            this.cleaning(false);
            if (!this.editor) {
                this.editor = tinymce.get(this.editorId);
                if (!this.editor) {
                    console.error('Invalid editor id ' + this.editorId);
                    return;
                }
            }
            this.content = this.editor.getContent();
            if (!this.content.trim()) {
                alert('No text to clean.');
                return;
            }
            // jQuery("#html-cleanup-modal").modal('show');
            Peanut.ui.helper.showModal("#html-cleanup-modal")
        };

        doCleanup = ()=> {
            let me = this;
            me.cleaning(true);
            let lines = me.content.split("\n");
            if (me.removeBlanks()) {
                lines = lines.filter((item: string) => {
                    if (item == '<p>&nbsp;</p>' || item == '<p><strong>&nbsp;</strong></p>') {
                        return false;
                    }
                    return true;
                });
            }
            if (me.convertHeadings()) {
                let conversion = me.convertHeadings();
                let h1 = conversion.substr(0, 2);
                let h2 = conversion.length > 2 ? 'h' + conversion.substr(-1) : h1;

                let hStart = '<' + h1 + '>';
                let hEnd = '</' + h1 + '>';
                let pStart = '<p><strong>';
                let pEnd = '</strong></p>';
                let count = lines.length;
                for (let i = 0; i < count; i++) {
                    let item = lines[i];
                    if (item.substr(0, 11) == pStart && item.substr(-13) == pEnd) {
                        lines[i] = item.replace(pStart, hStart).replace(pEnd, hEnd);
                        hStart = '<' + h2 + '>';
                        hEnd = '</' + h2 + '>';
                    }
                }
            }

            me.editor.setContent(lines.join("\n"));
            me.cleaning(false);
            // jQuery("#html-cleanup-modal").modal('hide');
            Peanut.ui.helper.hideModal("#html-cleanup-modal")
        };

    }

}
