import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import type Mithril from 'mithril';

export default class SitemapSettingsPage extends ExtensionPage {
  oninit(vnode: Mithril.Vnode) {
    super.oninit(vnode);
  }

  content() {
    const currentMode = this.setting('fof-sitemap.mode')();

    // Change setting value client-side so the Select reflects which option is effectively used
    if (currentMode === 'cache' || currentMode === 'cache-disk') {
      this.setting('fof-sitemap.mode')('multi-file');
    }

    return (
      <div className="ExtensionPage-settings FoFSitemapSettingsPage">
        <div className="container">
          {app.forum.attribute('fof-sitemap.usersIndexAvailable')
            ? this.buildSettingComponent({
                type: 'switch',
                setting: 'fof-sitemap.excludeUsers',
                label: app.translator.trans('fof-sitemap.admin.settings.exclude_users'),
                help: app.translator.trans('fof-sitemap.admin.settings.exclude_users_help'),
              })
            : null}

          <div className="Form-group">
            <h3>{app.translator.trans('fof-sitemap.admin.settings.soft_404.heading')}</h3>
            <p className="helpText">{app.translator.trans('fof-sitemap.admin.settings.soft_404.help')}</p>
            {app.forum.attribute('fof-sitemap.usersIndexAvailable')
              ? this.buildSettingComponent({
                  type: 'number',
                  setting: 'fof-sitemap.model.user.comments.minimum_item_threshold',
                  label: app.translator.trans('fof-sitemap.admin.settings.soft_404.user.comments.minimum_item_threshold_label'),
                  help: app.translator.trans('fof-sitemap.admin.settings.soft_404.user.comments.minimum_item_threshold_help'),
                  min: 0,
                  required: true,
                })
              : null}
            {app.initializers.has('flarum-tags')
              ? this.buildSettingComponent({
                  type: 'number',
                  setting: 'fof-sitemap.model.tags.discussion.minimum_item_threshold',
                  label: app.translator.trans('fof-sitemap.admin.settings.soft_404.tags.discussion.minimum_item_threshold_label'),
                  help: app.translator.trans('fof-sitemap.admin.settings.soft_404.tags.discussion.minimum_item_threshold_help'),
                  min: 0,
                  required: true,
                })
              : null}
          </div>

          {this.modeChoice()}

          <hr />
          <h3>{app.translator.trans('fof-sitemap.admin.settings.advanced_options_label')}</h3>
          <div className="Form-group">
            {this.buildSettingComponent({
              type: 'select',
              setting: 'fof-sitemap.frequency',
              options: {
                hourly: app.translator.trans('fof-sitemap.admin.settings.frequency.hourly'),
                'twice-daily': app.translator.trans('fof-sitemap.admin.settings.frequency.twice_daily'),
                daily: app.translator.trans('fof-sitemap.admin.settings.frequency.daily'),
              },
              label: app.translator.trans('fof-sitemap.admin.settings.frequency_label'),
            })}
          </div>

          {this.buildSettingComponent({
            type: 'switch',
            setting: 'fof-sitemap.riskyPerformanceImprovements',
            label: app.translator.trans('fof-sitemap.admin.settings.risky_performance_improvements'),
            help: app.translator.trans('fof-sitemap.admin.settings.risky_performance_improvements_help'),
          })}

          {this.submitButton()}
        </div>
      </div>
    );
  }

  modeChoice() {
    if (!app.forum.attribute('fof-sitemap.modeChoice')) {
      return null;
    }

    return (
      <div>
        {this.buildSettingComponent({
          type: 'select',
          setting: 'fof-sitemap.mode',
          options: {
            run: app.translator.trans('fof-sitemap.admin.settings.modes.runtime'),
            'multi-file': app.translator.trans('fof-sitemap.admin.settings.modes.multi_file'),
          },
          label: app.translator.trans('fof-sitemap.admin.settings.mode_label'),
        })}

        <p>{app.translator.trans('fof-sitemap.admin.settings.mode_help')}</p>

        <div>
          <h3>{app.translator.trans('fof-sitemap.admin.settings.mode_help_runtime_label')}</h3>
          <p>{app.translator.trans('fof-sitemap.admin.settings.mode_help_runtime')}</p>
        </div>
        <h4>{app.translator.trans('fof-sitemap.admin.settings.mode_help_schedule')}</h4>
        <p>
          {app.translator.trans('fof-sitemap.admin.settings.mode_help_schedule_setup', {
            a: <a href="https://docs.flarum.org/console/#schedulerun" target="_blank" rel="noopener"></a>,
          })}
        </p>
        <div>
          <h3>{app.translator.trans('fof-sitemap.admin.settings.mode_help_multi_label')}</h3>
          <p>{app.translator.trans('fof-sitemap.admin.settings.mode_help_multi')}</p>
        </div>
      </div>
    );
  }
}
