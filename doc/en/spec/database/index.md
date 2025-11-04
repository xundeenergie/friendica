# Database Tables

| Table | Description |
|-------|-------------|
| [2fa_app_specific_password](help/spec/database/db_2fa_app_specific_password) | Two-factor app-specific _password |
| [2fa_recovery_codes](help/spec/database/db_2fa_recovery_codes) | Two-factor authentication recovery codes |
| [2fa_trusted_browser](help/spec/database/db_2fa_trusted_browser) | Two-factor authentication trusted browsers |
| [account-suggestion](help/spec/database/db_account-suggestion) | Account suggestion |
| [account-user](help/spec/database/db_account-user) | Remote and local accounts |
| [apcontact](help/spec/database/db_apcontact) | ActivityPub compatible contacts - used in the ActivityPub implementation |
| [application](help/spec/database/db_application) | OAuth application |
| [application-marker](help/spec/database/db_application-marker) | Timeline marker |
| [application-token](help/spec/database/db_application-token) | OAuth user token |
| [arrived-activity](help/spec/database/db_arrived-activity) | Id of arrived activities |
| [attach](help/spec/database/db_attach) | file attachments |
| [cache](help/spec/database/db_cache) | Stores temporary data |
| [channel](help/spec/database/db_channel) | User defined Channels |
| [check-full-text-search](help/spec/database/db_check-full-text-search) | Check for a full text search match in user defined channels before storing the message in the system |
| [config](help/spec/database/db_config) | main configuration storage |
| [contact](help/spec/database/db_contact) | contact table |
| [contact-relation](help/spec/database/db_contact-relation) | Contact relations |
| [conv](help/spec/database/db_conv) | private messages |
| [delayed-post](help/spec/database/db_delayed-post) | Posts that are about to be distributed at a later time |
| [delivery-queue](help/spec/database/db_delivery-queue) | Delivery data for posts for the batch processing |
| [diaspora-contact](help/spec/database/db_diaspora-contact) | Diaspora compatible contacts - used in the Diaspora implementation |
| [diaspora-interaction](help/spec/database/db_diaspora-interaction) | Signed Diaspora Interaction |
| [endpoint](help/spec/database/db_endpoint) | ActivityPub endpoints - used in the ActivityPub implementation |
| [event](help/spec/database/db_event) | Events |
| [fetch-entry](help/spec/database/db_fetch-entry) |  |
| [fetched-activity](help/spec/database/db_fetched-activity) | Id of fetched activities |
| [fsuggest](help/spec/database/db_fsuggest) | friend suggestion stuff |
| [group](help/spec/database/db_group) | privacy circles, circle info |
| [group_member](help/spec/database/db_group_member) | privacy circles, member info |
| [gserver](help/spec/database/db_gserver) | Global servers |
| [gserver-tag](help/spec/database/db_gserver-tag) | Tags that the server has subscribed |
| [hook](help/spec/database/db_hook) | addon hook registry |
| [inbox-entry](help/spec/database/db_inbox-entry) | Incoming activity |
| [inbox-entry-receiver](help/spec/database/db_inbox-entry-receiver) | Receiver for the incoming activity |
| [inbox-status](help/spec/database/db_inbox-status) | Status of ActivityPub inboxes |
| [intro](help/spec/database/db_intro) |  |
| [item-uri](help/spec/database/db_item-uri) | URI and GUID for items |
| [key-value](help/spec/database/db_key-value) | A key value storage |
| [locks](help/spec/database/db_locks) |  |
| [mail](help/spec/database/db_mail) | private messages |
| [mailacct](help/spec/database/db_mailacct) | Mail account data for fetching mails |
| [manage](help/spec/database/db_manage) | table of accounts that can manage each other |
| [notification](help/spec/database/db_notification) | notifications |
| [notify](help/spec/database/db_notify) | [Deprecated] User notifications |
| [notify-threads](help/spec/database/db_notify-threads) |  |
| [openwebauth-token](help/spec/database/db_openwebauth-token) | Store OpenWebAuth token to verify contacts |
| [parsed_url](help/spec/database/db_parsed_url) | cache for 'parse_url' queries |
| [pconfig](help/spec/database/db_pconfig) | personal (per user) configuration storage |
| [permissionset](help/spec/database/db_permissionset) |  |
| [photo](help/spec/database/db_photo) | photo storage |
| [post](help/spec/database/db_post) | Structure for all posts |
| [post-activity](help/spec/database/db_post-activity) | Original remote activity |
| [post-category](help/spec/database/db_post-category) | post relation to categories |
| [post-collection](help/spec/database/db_post-collection) | Collection of posts |
| [post-content](help/spec/database/db_post-content) | Content for all posts |
| [post-counts](help/spec/database/db_post-counts) | Original remote activity |
| [post-delivery](help/spec/database/db_post-delivery) | Delivery data for posts for the batch processing |
| [post-delivery-data](help/spec/database/db_post-delivery-data) | Delivery data for items |
| [post-engagement](help/spec/database/db_post-engagement) | Engagement data per post |
| [post-history](help/spec/database/db_post-history) | Post history |
| [post-link](help/spec/database/db_post-link) | Post related external links |
| [post-media](help/spec/database/db_post-media) | Attached media |
| [post-origin](help/spec/database/db_post-origin) | Posts from local users |
| [post-question](help/spec/database/db_post-question) | Question |
| [post-question-option](help/spec/database/db_post-question-option) | Question option |
| [post-searchindex](help/spec/database/db_post-searchindex) | Content for all posts |
| [post-tag](help/spec/database/db_post-tag) | post relation to tags |
| [post-thread](help/spec/database/db_post-thread) | Thread related data |
| [post-thread-user](help/spec/database/db_post-thread-user) | Thread related data per user |
| [post-user](help/spec/database/db_post-user) | User specific post data |
| [post-user-notification](help/spec/database/db_post-user-notification) | User post notifications |
| [process](help/spec/database/db_process) | Currently running system processes |
| [profile](help/spec/database/db_profile) | user profiles data |
| [profile_field](help/spec/database/db_profile_field) | Custom profile fields |
| [register](help/spec/database/db_register) | registrations requiring admin approval |
| [report](help/spec/database/db_report) |  |
| [report-post](help/spec/database/db_report-post) | Individual posts attached to a moderation report |
| [report-rule](help/spec/database/db_report-rule) | Terms of service rule lines relevant to a moderation report |
| [search](help/spec/database/db_search) |  |
| [session](help/spec/database/db_session) | web session storage |
| [storage](help/spec/database/db_storage) | Data stored by Database storage backend |
| [subscription](help/spec/database/db_subscription) | Push Subscription for the API |
| [tag](help/spec/database/db_tag) | tags and mentions |
| [user](help/spec/database/db_user) | The local users |
| [user-contact](help/spec/database/db_user-contact) | User specific public contact data |
| [user-gserver](help/spec/database/db_user-gserver) | User settings about remote servers |
| [userd](help/spec/database/db_userd) | Deleted usernames |
| [verb](help/spec/database/db_verb) | Activity Verbs |
| [worker-ipc](help/spec/database/db_worker-ipc) | Inter process communication between the frontend and the worker |
| [workerqueue](help/spec/database/db_workerqueue) | Background tasks queue entries |
