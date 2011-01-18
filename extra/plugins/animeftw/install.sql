
INSERT INTO configs ( `key`, `value`, `default`, `section`, type, label, description, class ) VALUES (
	'animeftw.auth.username', '', '', 'plugins', 0, 'p_animeftw_conf_authusername_label', 'p_animeftw_conf_authusername_desc', '');
INSERT INTO configs ( `key`, `value`, `default`, `section`, type, label, description, class ) VALUES (
	'animeftw.auth.password', '', '', 'plugins', 0, 'p_animeftw_conf_authpassword_label', 'p_animeftw_conf_authpassword_desc', '');
INSERT INTO configs ( `key`, `value`, `default`, `section`, type, label, description, class ) VALUES (
	'animeftw.index.series.url', 'http://www.animeftw.tv/videos', 'http://www.animeftw.tv/videos', 'plugins', 0, 'p_animeftw_conf_indexseriesurl_label', 'p_animeftw_conf_indexseriesurl_desc', '');
INSERT INTO configs ( `key`, `value`, `default`, `section`, type, label, description, class ) VALUES (
	'animeftw.index.movies.url', 'http://www.animeftw.tv/movies', 'http://www.animeftw.tv/movies', 'plugins', 0, 'p_animeftw_conf_indexmoviesurl_label', 'p_animeftw_conf_indexmoviesurl_desc', '');
INSERT INTO configs ( `key`, `value`, `default`, `section`, type, label, description, class ) VALUES (
	'animeftw.index.oav.url', 'http://www.animeftw.tv/ovas', 'http://www.animeftw.tv/ovas', 'plugins', 0, 'p_animeftw_conf_indexoavurl_label', 'p_animeftw_conf_indexoavurl_desc', '');
INSERT INTO configs ( `key`, `value`, `default`, `section`, type, label, description, class ) VALUES (
	'animeftw.base.url', 'http://www.animeftw.tv/', 'http://www.animeftw.tv/', 'plugins', 0, 'p_animeftw_conf_baseurl_label', 'p_animeftw_conf_baseurl_desc', '');
INSERT INTO configs ( `key`, `value`, `default`, `section`, type, label, description, class ) VALUES (
	'animeftw.login.url', 'https://www.animeftw.tv/login', 'https://www.animeftw.tv/login', 'plugins', 0, 'p_animeftw_conf_loginurl_label', 'p_animeftw_conf_loginurl_desc', '');
INSERT INTO configs ( `key`, `value`, `default`, `section`, type, label, description, class ) VALUES (
	'animeftw.request.timeout', '25', '25', 'plugins', 0, 'p_animeftw_conf_requesttimeout_label', 'p_animeftw_conf_requesttimeout_desc', '');
INSERT INTO configs ( `key`, `value`, `default`, `section`, type, label, description, class ) VALUES (
	'animeftw.request.maxredirects', '10', '10', 'plugins', 0, 'p_animeftw_conf_requestredirects_label', 'p_animeftw_conf_requestredirects_desc', '');
INSERT INTO configs ( `key`, `value`, `default`, `section`, type, label, description, class ) VALUES (
	'animeftw.hide.useragent', '1', '1', 'plugins', 3, 'p_animeftw_conf_hideuseragent_label', 'p_animeftw_conf_hideuseragent_desc',	'');
	
UPDATE plugins SET enabled=1 WHERE key = "animeftw";

