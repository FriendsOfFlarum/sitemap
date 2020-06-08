import app from 'flarum/app';
import { settings } from '@fof-components';

const {
    SettingsModal,
    items: { BooleanItem, SelectItem },
} = settings;

app.initializers.add('fof/sitemap', () => {
    app.extensionSettings['fof-sitemap'] = () =>
        app.modal.show(
            new SettingsModal({
                title: app.translator.trans('fof-sitemap.admin.settings.title'),
                type: 'medium',
                items: [
                    <div className='Form-group'>
                        <label>
                            {app.translator.trans(
                                'fof-sitemap.admin.settings.mode_label'
                            )}
                        </label>

                        {SelectItem.component({
                            options: {
                                'run': app.translator.trans('fof-sitemap.admin.settings.modes.runtime'),
                                'cache': app.translator.trans('fof-sitemap.admin.settings.modes.cache'),
                                'cache-disk': app.translator.trans('fof-sitemap.admin.settings.modes.cache_disk'),
                                'multi-file': app.translator.trans('fof-sitemap.admin.settings.modes.multi_file'),
                            },
                            key: 'fof-sitemap.mode',
                            required: true
                        })}
                    </div>,
                    <p>
                        {app.translator.trans(
                            'fof-sitemap.admin.settings.mode_help'
                        )}
                    </p>,

                    <div>
                        <h3>{app.translator.trans('fof-sitemap.admin.settings.mode_help_runtime_label')}</h3>
                        <p>{app.translator.trans('fof-sitemap.admin.settings.mode_help_runtime')}</p>
                    </div>,
                    <h4>{app.translator.trans('fof-sitemap.admin.settings.mode_help_schedule')}</h4>,
                    <p>Without the Flarum scheduler correctly setup, the following modes will not run. Refer here <a href="https://discuss.flarum.org/d/24118" target="_blank">for more information.</a></p>,
                    <div>
                        <h3>{app.translator.trans('fof-sitemap.admin.settings.mode_help_cache_disk_label')}</h3>
                        <p>{app.translator.trans('fof-sitemap.admin.settings.mode_help_cache_disk')}</p>
                    </div>,
                    <h4>{app.translator.trans('fof-sitemap.admin.settings.mode_help_large')}</h4>,
                    <div>
                        <h3>{app.translator.trans('fof-sitemap.admin.settings.mode_help_multi_label')}</h3>
                        <p>{app.translator.trans('fof-sitemap.admin.settings.mode_help_multi')}</p>
                    </div>,
                    <hr />,
                    <h3>{app.translator.trans('fof-sitemap.admin.settings.advanced_options_label')}</h3>,
                    <div className='Form-group'>
                        <label>
                            {app.translator.trans(
                                'fof-sitemap.admin.settings.frequency_label'
                            )}
                        </label>

                        {SelectItem.component({
                            options: {
                                'hourly': app.translator.trans('fof-sitemap.admin.settings.frequency.hourly'),
                                'twice-daily': app.translator.trans('fof-sitemap.admin.settings.frequency.twice_daily'),
                                'daily': app.translator.trans('fof-sitemap.admin.settings.frequency.daily'),
                            },
                            key: 'fof-sitemap.frequency',
                            required: true
                        })}
                    </div>,
                ],
            })
        );
});
