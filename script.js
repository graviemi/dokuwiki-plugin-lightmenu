document.addEventListener('DOMContentLoaded', function () {
	const urlParams = new URLSearchParams(window.location.search);
	let id = urlParams.get('id');
	if ((el = document.getElementById(`lm-${id}`)) !== null)
		el.classList.add('current');
	while (id.length > 0)
	{
		let el = document.getElementById(`lm-${id}`);
		if (el !== null)
			el.checked = true;
		id = id.substring(0,id.lastIndexOf(':'));
	}
});
