document.addEventListener('mauticPageEventDelivered', function (e) {
	var response = e.detail.response

	var postsSegmentWrapper = document.querySelectorAll('.wpmautic-posts-segment')

	var contactId = '0'
	if ( response && response.success === 1) {
		contactId = response.id
	}

	postsSegmentWrapper.forEach(function (element) {

		var postType = element.dataset.postType
		var order = element.dataset.order
		var postsNumber = element.dataset.postsNumber

		var request = new XMLHttpRequest()
		request.open('POST', wpmauticScriptsL10n.adminUrl, true)
		request.setRequestHeader("Content-type", "application/x-www-form-urlencoded")

		request.onload = function () {
			if (this.status >= 200 && this.status < 400) {
				// Success!
				var data = JSON.parse(this.response)
				var txt = document.createTextNode(data)
				element.innerHTML = txt.textContent

				var event = new Event('mauticPostLoaded')
				document.dispatchEvent(event)
			}
		}


		request.send('contactId=' + contactId + '&postType=' + postType + '&order=' + order + '&postsNumber=' + postsNumber + '&action=mautic-get-segment-post')
	})

})
