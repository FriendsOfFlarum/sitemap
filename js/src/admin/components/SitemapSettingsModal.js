import SettingsModal from 'flarum/components/SettingsModal';
import { settings } from '@fof-components';

const {
    items: { SelectItem },
} = settings;

export default class AuthSettingsModal extends SettingsModal {
    title() {
        return app.translator.trans('fof-sitemap.admin.settings.title');
    }

    className() {
        return 'SitemapSettingsModal Modal Modal--medium';
    }

    form() {
        return [
            <div className="Form-group">
                <label>{app.translator.trans('fof-sitemap.admin.settings.mode_label')}</label>

                {SelectItem.component({
                    options: {
                        run: app.translator.trans('fof-sitemap.admin.settings.modes.runtime'),
                        cache: app.translator.trans('fof-sitemap.admin.settings.modes.cache'),
                        'cache-disk': app.translator.trans('fof-sitemap.admin.settings.modes.cache_disk'),
                        'multi-file': app.translator.trans('fof-sitemap.admin.settings.modes.multi_file'),
                    },
                    name: 'fof-sitemap.mode',
                    setting: this.setting.bind(this),
                    required: true,
                })}
            </div>,
            <p>{app.translator.trans('fof-sitemap.admin.settings.mode_help')}</p>,

            <div>
                <h3>{app.translator.trans('fof-sitemap.admin.settings.mode_help_runtime_label')}</h3>
                <p>{app.translator.trans('fof-sitemap.admin.settings.mode_help_runtime')}</p>
            </div>,
            <h4>{app.translator.trans('fof-sitemap.admin.settings.mode_help_schedule')}</h4>,
            <p>
                {app.translator.trans('fof-sitemap.admin.settings.mode_help_schedule_setup', {
                    a: <a href="https://discuss.flarum.org/d/24118" target="_blank"></a>,
                })}
            </p>,
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
            <div className="Form-group">
                <label>{app.translator.trans('fof-sitemap.admin.settings.frequency_label')}</label>

                {SelectItem.component({
                    options: {
                        hourly: app.translator.trans('fof-sitemap.admin.settings.frequency.hourly'),
                        'twice-daily': app.translator.trans('fof-sitemap.admin.settings.frequency.twice_daily'),
                        daily: app.translator.trans('fof-sitemap.admin.settings.frequency.daily'),
                    },
                    name: 'fof-sitemap.frequency',
                    setting: this.setting.bind(this),
                    required: true,
                })}
            </div>,
        ];
    }
}
