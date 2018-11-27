<h2>Änderungen in CiviCRM an Navision Kontakten seit {$timestamp}</h2>

<h2>
    Kontakt: {$contact_id}
</h2>
<h4>
    <p>
        Navision Id: {$navision_id}
    </p>
    <p>
        Kreditor Id: {$creditor_id}
    </p>
    <p>
        Debitor Id: {$debitor_id}
    </p>
</h4>

{foreach from=$contact_data item=entity_data key=entity_name}
    <h3>{$entity_name}</h3>
    <table style="width: 80%; table-layout: fixed;  border-collapse: collapse;">
        <thead>
        <tr>
            <th style="text-align: left;min-width: 500px; border: 1px solid black; padding: 0.5em;">Attribut</th>
            <th style="text-align: left;min-width: 300px; border: 1px solid black; padding: 0.5em;">Alt</th>
            <th style="text-align: left;min-width: 300px; border: 1px solid black; padding: 0.5em;">Neu</th>
        </tr>
        <tbody>
        {foreach from=$entity_data item=data key=attribute}
        <tr>
            <th style="text-align: left;min-width: 500px; border: 1px solid black; padding: 0.5em;">{$data.translation}</th>
            <td style="text-align: left;min-width: 300px; border: 1px solid black; padding: 0.5em;">{$data.old}</td>
            <td style="text-align: left;min-width: 300px; border: 1px solid black; padding: 0.5em;">{$data.new}</td>
        </tr>
        </tbody>
        {/foreach}
        </thead>
    </table>
{/foreach}
