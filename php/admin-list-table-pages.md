# Admin List Table Pages

There are several PHP programs in the `www` repository history for updating tables in wp-admin using WP_List_Table. These were never finished, and were removed in the commit tagged `remove-admin-list-tables`.

The tables updated are `sl_competition_group`, `sl_team_abbrev`, and `sl_winner` - though only the team_abbrev code is mostly complete.

Useful Git commands:

* `git show remove-admin-list-tables` - see complete commit
* `git show remove-admin-list-tables --name-only` - list files in commit
* `git restore -s "remove-admin-list-tables^" -- pathspec` - restore a particular file
