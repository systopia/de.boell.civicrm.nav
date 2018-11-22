<h2>Änderungen in CiviCRM an Navision Kontakten seit {$timestamp}</h2>

<h2>
    Kontakt: {$contact_id}
</h2>
<h4>
    <p>
        NavisionId: {$navision_id}
    </p>
    <p>
        KreditorId: {$creditor_id}
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
            <th style="text-align: left;min-width: 300px; border: 1px solid black; padding: 0.5em;">old</th>
            <th style="text-align: left;min-width: 300px; border: 1px solid black; padding: 0.5em;">new</th>
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
