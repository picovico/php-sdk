<?php

/** Picovico URLs **/

class PicovicoUrl{
	const login = "login";
	const app_authenticate = "login/app";

	const begin_project = "me/videos";
	const get_videos = "me/videos";
	const single_video = "me/videos/%s";
	const save_video = "me/videos/%s";
	const preview_video = "me/videos/%s/preview";
	const create_video = "me/videos/%s/render";
	const duplicate_video = "me/videos/%s/duplicate";
	const get_draft = "me/draft";

	const get_musics = "me/musics";
	const upload_music = "me/musics";
	const single_music = "me/musics/%s";

	const upload_photo = "me/photos";
	const get_photos = "me/photos";
	const single_photo = "me/photos/%s";

	const get_styles = "me/styles";
	const get_public_styles = "styles";

	const me = "me/";
	const change_password = "me/changepassword";
}
