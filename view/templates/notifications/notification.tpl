
<style>
	/* Flexbox layout to align the icon and text in a single line */
	.notif-item a {
		display: flex;
		align-items: flex-start; /* Aligns items at the start of the flex container */
	}

	/* Margin to create space between the icon and the text */
	.notif-image {
		margin-right: 10px; /* Adjust the space between the icon and text as needed */

		}

	/* Styles to ensure the text wraps properly after 70 characters */
	.notif-text {
		display: inline-block; /* Allows the text to be constrained within a block-level element */
		max-width: 70ch; /* Limits the maximum width of the text to 70 characters */
		overflow-wrap: break-word; /* Ensures that words will break if necessary to fit the width */
	}
</style>

<div class="notif-item {{if !$item_seen}}unseen{{/if}}" {{if $item_seen}}aria-hidden="true"{{/if}}>
	<a href="{{$notification.link}}">
		<img src="{{$notification.image}}" aria-hidden="true" class="notif-image">
		<span class="notif-text">{{$notification.text}}</span>
		<span class="notif-when">{{$notification.ago}}</span>
	</a>
</div>
