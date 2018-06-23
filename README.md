# Embed H5P Activity #

This filter embeds H5P activities within other pages in a course like
pages in books, lessons, or course page.

To install, place the directory contents in *filter/hvp*, and visit the
admin settings to complete the plugin install. Afterward go to *Plugins
-> Manage filters*, and enable this filter or make it available. Change the
*Apply to* setting to *Contents and headings* to have the filter embed
the HP5 Content directly on the page rather than in a frame (experimental).

To embed an H5P activity, first include the H5P file as an activity
within the Moodle course. Give it a unique name and set the availablity
to *Make available but not shown on course page*. Edit the page where
the activity should be displayed, enter the name of the H5P activity,
and save the page. Enable the filter, and disable Activity autolinking
filter on the page. The HP$ should appear.

## License ##

2018 Daniel Thies <dethies@gmail.com>

This program is free software: you can redistribute it and/or modify it
under the terms of the GNU General Public License as published by the
Free Software Foundation, either version 3 of the License, or (at your
option) any later version.

This program is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License
for more details.

You should have received a copy of the GNU General Public License along
with this program.  If not, see <http://www.gnu.org/licenses/>.
