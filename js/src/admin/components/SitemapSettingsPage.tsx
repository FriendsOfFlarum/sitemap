import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Button from 'flarum/common/components/Button';
import type Mithril from 'mithril';
import humanTime from 'flarum/common/helpers/humanTime';

export default class SitemapSettingsPage extends ExtensionPage {
  loading: boolean = false;

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
          {/* Operation Mode & Build Controls */}
          {this.renderModeSection()}

          {/* Content Exclusion Settings */}
          {this.renderExclusionSettings()}

          {/* Soft 404 Prevention */}
          {this.renderSoft404Settings()}

          {/* Advanced Options */}
          {this.renderAdvancedOptions()}

          {this.submitButton()}
        </div>
      </div>
    );
  }

  renderModeSection() {
    const hasModeChoice = app.forum.attribute('fof-sitemap.modeChoice');
    const showBuildButton = this.isCachedMode();
    const lastBuildTime = this.formatLastBuildTime();

    return (
      <>
        {hasModeChoice && (
          <div className="Form-group">
            {this.buildSettingComponent({
              type: 'select',
              setting: 'fof-sitemap.mode',
              options: {
                run: app.translator.trans('fof-sitemap.admin.settings.modes.runtime'),
                'multi-file': app.translator.trans('fof-sitemap.admin.settings.modes.multi_file'),
              },
              label: app.translator.trans('fof-sitemap.admin.settings.mode_label'),
            })}

            <p className="helpText">{app.translator.trans('fof-sitemap.admin.settings.mode_help')}</p>

            <div>
              <h4>{app.translator.trans('fof-sitemap.admin.settings.mode_help_runtime_label')}</h4>
              <p className="helpText">{app.translator.trans('fof-sitemap.admin.settings.mode_help_runtime')}</p>
            </div>

            <div>
              <h4>{app.translator.trans('fof-sitemap.admin.settings.mode_help_multi_label')}</h4>
              <p className="helpText">{app.translator.trans('fof-sitemap.admin.settings.mode_help_multi')}</p>
              <p className="helpText">
                <strong>{app.translator.trans('fof-sitemap.admin.settings.mode_help_schedule')}</strong>{' '}
                {app.translator.trans('fof-sitemap.admin.settings.mode_help_schedule_setup', {
                  a: <a href="https://docs.flarum.org/console/#schedulerun" target="_blank" rel="noopener"></a>,
                })}
              </p>
            </div>
          </div>
        )}

        {showBuildButton && (
          <>
            {lastBuildTime && (
              <div className="Form-group">
                <label>
                  <strong>{app.translator.trans('fof-sitemap.admin.settings.last_build_time')}</strong>
                </label>
                <p className="helpText">{lastBuildTime}</p>
              </div>
            )}

            <div className="Form-group">
              <Button className="Button Button--primary" onclick={() => this.buildSitemap()} loading={this.loading} disabled={this.loading}>
                {app.translator.trans('fof-sitemap.admin.settings.build_button')}
              </Button>
              <p className="helpText">{app.translator.trans('fof-sitemap.admin.settings.build_button_help')}</p>
            </div>
          </>
        )}

        {(hasModeChoice || showBuildButton) && <hr />}
      </>
    );
  }

  renderExclusionSettings() {
    const hasUsersIndex = app.forum.attribute('fof-sitemap.usersIndexAvailable');
    const hasTags = app.initializers.has('flarum-tags');

    if (!hasUsersIndex && !hasTags) return null;

    return (
      <>
        {hasUsersIndex &&
          this.buildSettingComponent({
            type: 'switch',
            setting: 'fof-sitemap.excludeUsers',
            label: app.translator.trans('fof-sitemap.admin.settings.exclude_users'),
            help: app.translator.trans('fof-sitemap.admin.settings.exclude_users_help'),
          })}

        {hasTags &&
          this.buildSettingComponent({
            type: 'switch',
            setting: 'fof-sitemap.excludeTags',
            label: app.translator.trans('fof-sitemap.admin.settings.exclude_tags'),
            help: app.translator.trans('fof-sitemap.admin.settings.exclude_tags_help'),
          })}

        <hr />
      </>
    );
  }

  renderSoft404Settings() {
    const hasUsersIndex = app.forum.attribute('fof-sitemap.usersIndexAvailable');
    const hasTags = app.initializers.has('flarum-tags');

    if (!hasUsersIndex && !hasTags) return null;

    return (
      <>
        <div className="Form-group">
          <h3>{app.translator.trans('fof-sitemap.admin.settings.soft_404.heading')}</h3>
          <p className="helpText">{app.translator.trans('fof-sitemap.admin.settings.soft_404.help')}</p>

          {hasUsersIndex &&
            this.buildSettingComponent({
              type: 'number',
              setting: 'fof-sitemap.model.user.comments.minimum_item_threshold',
              label: app.translator.trans('fof-sitemap.admin.settings.soft_404.user.comments.minimum_item_threshold_label'),
              help: app.translator.trans('fof-sitemap.admin.settings.soft_404.user.comments.minimum_item_threshold_help'),
              min: 0,
              required: true,
            })}

          {hasTags &&
            this.buildSettingComponent({
              type: 'number',
              setting: 'fof-sitemap.model.tags.discussion.minimum_item_threshold',
              label: app.translator.trans('fof-sitemap.admin.settings.soft_404.tags.discussion.minimum_item_threshold_label'),
              help: app.translator.trans('fof-sitemap.admin.settings.soft_404.tags.discussion.minimum_item_threshold_help'),
              min: 0,
              required: true,
            })}
        </div>

        <hr />
      </>
    );
  }

  renderAdvancedOptions() {
    return (
      <>
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

        {this.buildSettingComponent({
          type: 'switch',
          setting: 'fof-sitemap.include_priority',
          label: app.translator.trans('fof-sitemap.admin.settings.include_priority'),
          help: app.translator.trans('fof-sitemap.admin.settings.include_priority_help'),
        })}

        {this.buildSettingComponent({
          type: 'switch',
          setting: 'fof-sitemap.include_changefreq',
          label: app.translator.trans('fof-sitemap.admin.settings.include_changefreq'),
          help: app.translator.trans('fof-sitemap.admin.settings.include_changefreq_help'),
        })}
      </>
    );
  }

  buildSitemap() {
    this.loading = true;

    app
      .request({
        method: 'DELETE',
        url: app.forum.attribute('apiUrl') + '/fof-sitemap/build',
      })
      .then(() => {
        app.alerts.show({ type: 'success' }, app.translator.trans('fof-sitemap.admin.settings.build_success'));

        // Note: The last build time will be updated by the Generator when it completes
        // To see the updated time, refresh the page after the job finishes
      })
      .catch((error) => {
        app.alerts.show({ type: 'error' }, app.translator.trans('fof-sitemap.admin.settings.build_error'));
        console.error('Sitemap build failed:', error);
      })
      .finally(() => {
        this.loading = false;
        m.redraw();
      });
  }

  isCachedMode(): boolean {
    const mode = this.setting('fof-sitemap.mode')();
    const forceCached = !app.forum.attribute('fof-sitemap.modeChoice');
    return mode !== 'run' || forceCached;
  }

  formatLastBuildTime() {
    const timestamp = app.data.settings['fof-sitemap.last_build_time'];
    if (!timestamp) return null;

    const date = new Date(parseInt(timestamp) * 1000);
    return humanTime(date);
  }
}
