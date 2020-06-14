<?php
 
function xmldb_cado_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();
    $result = TRUE;
 
/*    if ($oldversion < 2020022100) {

        // Define field id to be added to cado.
        $table = new xmldb_table('cado');
        $field = new xmldb_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);

        // Conditionally launch add field id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Cado savepoint reached.
        upgrade_mod_savepoint(true, 2020022100, 'cado');
    }

    */
    return $result;
}
?>