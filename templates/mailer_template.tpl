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
    <table style="width: 80%; table-layout: fixed; border: 1px; border-collapse: collapse;">
        <thead>
        <tr>
            <th style="text-align: left;min-width: 500px;">Attribut</th>
            <th style="text-align: left;min-width: 300px;">old</th>
            <th style="text-align: left;min-width: 300px;">new</th>
        </tr>
        <tbody>
        {foreach from=$entity_data item=data key=attribute}
        <tr>
            <th style="text-align: left;min-width: 500px;">{$attribute}</th>
            <td style="text-align: left;min-width: 300px;">{$data.old}</td>
            <td style="text-align: left;min-width: 300px;">{$data.new}</td>
        </tr>
        </tbody>
        {/foreach}
        </thead>
    </table>
{/foreach}
