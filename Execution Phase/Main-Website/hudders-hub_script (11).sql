prompt --application/set_environment
set define off verify off feedback off
whenever sqlerror exit sql.sqlcode rollback
--------------------------------------------------------------------------------
--
-- Oracle APEX export file
--
-- You should run this script using a SQL client connected to the database as
-- the owner (parsing schema) of the application or as a database user with the
-- APEX_ADMINISTRATOR_ROLE role.
--
-- This export file has been automatically generated. Modifying this file is not
-- supported by Oracle and can lead to unexpected application and/or instance
-- behavior now or in the future.
--
-- NOTE: Calls to apex_application_install override the defaults below.
--
--------------------------------------------------------------------------------
begin
wwv_flow_imp.import_begin (
 p_version_yyyy_mm_dd=>'2026.03.30'
,p_default_workspace_id=>5401966762507087
);
end;
/
-- Oracle APEX 26.1.0 SQL Script Export file
-- Exported 19:36 Thursday May 21, 2026 by: ADMIN
-- Scripts included:
--      ADMIN
 
begin wwv_flow.g_user := nvl(wwv_flow.g_user,'ADMIN'); end;
/
prompt --application/sql/scripts
prompt ...exporting script file
--
begin
wwv_flow_imp.g_varchar2_table := wwv_flow_imp.empty_varchar2_table;
null;
end;
/
 
declare
  l_name   varchar2(255);
begin
  l_name   := 'F6975501519918871/hudders_hub_auto_seq_trigger';
  wwv_imp_workspace.import_script(
    p_name          => l_name,
    p_varchar2_table=> wwv_flow_imp.g_varchar2_table,
    p_pathid=> null,
    p_filename=> 'hudders_hub_auto_seq_trigger',
    p_title=> 'hudders_hub_auto_seq_trigger',
    p_mime_type=> 'text/plain',
    p_dad_charset=> '',
    p_deleted_as_of=> to_date('00010101000000','YYYYMMDDHH24MISS'),
    p_content_type=> 'BLOB',
    p_language=> '',
    p_description=> '',
    p_file_type=> 'SCRIPT',
    p_file_charset=> 'utf-8');
null;
end;
/
begin
wwv_flow_imp.import_end(p_auto_install_sup_obj => nvl(wwv_flow_application_install.get_auto_install_sup_obj, false)
);
--commit;
end;
/
set verify on feedback on define on
prompt  ...done
