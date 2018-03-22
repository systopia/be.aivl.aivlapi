{*-------------------------------------------------------+
| Amnesty Iternational Vlaanderen Custom API             |
| Copyright (C) 2018 SYSTOPIA                            |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+-------------------------------------------------------*}

<p>This seems to be an update or re-submission of the <a href="{crmURL p="civicrm/contact/view/participant" q="action=view&reset=1&id=$participant_id&cid=$contact_id"}">registration [{$participant_id}]</a>.</p>

<div>
Submission:
<table>
  <thead>
    <tr>
      <th>Attribute</th>
      <th>Submitted Value</th>
    </tr>
  </thead>
  <tbody>
{foreach from=$data item=value key=name}
    <tr>
      <td>{$name}</td>
      <td>{$value}</td>
    </tr>
{/foreach}
  </tbody>
</table>
</div>