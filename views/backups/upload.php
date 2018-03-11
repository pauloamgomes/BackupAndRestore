<div>
    <ul class="uk-breadcrumb">
        <li><a href="@route('/backup-and-restore')">@lang('Backup And Restore')</a></li>
        <li class="uk-active"><span>@lang('Upload')</span></li>
    </ul>
</div>

<div class="uk-margin-top uk-grid" riot-view>

    <div class="uk-margin">
        <div ref="uploadprogress" class="uk-margin uk-hidden">
            <div class="uk-progress">
                <div ref="progressbar" class="uk-progress-bar" style="width: 0%;">&nbsp;</div>
            </div>
        </div>
    </div>

    <div class="uk-margin">
        <span class="uk-button uk-button-large uk-button-primary uk-margin-small-right uk-form-file">
            <input class="js-upload-select" type="file" multiple="true">
            <i class="uk-icon-upload"></i> @lang('Upload Backup')
        </span>
    </div>

    <script type="view/script">
        var $this = this;
        this.currentpath = App.session.get('app.finder.path');
        this.backupsPath = {{ json_encode($backupsPath) }};

        this.on('mount', function(){

            console.log(this.backupsPath);

            App.assets.require(['/assets/lib/uikit/js/components/upload.js'], function() {

                var uploadSettings = {

                        action: App.route('/media/api'),
                        params: { "cmd": "upload" },
                        type: 'json',
                        before: function(options) {
                            options.params.path = $this.backupsPath;
                            console.log(options);
                        },
                        loadstart: function() {
                            $this.refs.uploadprogress.classList.remove('uk-hidden');
                        },
                        progress: function(percent) {

                            percent = Math.ceil(percent) + '%';

                            $this.refs.progressbar.innerHTML   = '<span>'+percent+'</span>';
                            $this.refs.progressbar.style.width = percent;
                        },
                        allcomplete: function(response) {

                            $this.refs.uploadprogress.classList.add('uk-hidden');

                            if (response && response.failed && response.failed.length) {
                                App.ui.notify("File(s) failed to uploaded.", "danger");
                            }

                            if (!response) {
                                App.ui.notify("Something went wrong.", "danger");
                            }

                            if (response && response.uploaded && response.uploaded.length) {
                                App.ui.notify("File(s) uploaded.", "success");
                                $this.loadPath();
                            }

                        }
                },

                uploadselect = UIkit.uploadSelect(App.$('.js-upload-select', $this.root)[0], uploadSettings);
                // uploaddrop   = UIkit.uploadDrop($this.root, uploadSettings);

                UIkit.init(this.root);
            });
        });

        submit(e) {
            if(e) e.preventDefault();

        }


    </script>

</div>
