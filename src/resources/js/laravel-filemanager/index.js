export default function (config) {
  const { hooks, element } = window.wp
  const { Component } = element

  class LaravelFilemanager extends Component {
    constructor (props) {
        props.gallery = true;
        props.multiple = true;
        props.addToGallery = true;
      super(props)
      this.state = {
        media: []
      }
    }

    getMediaType = (path) => {
      const video = ['mp4', 'm4v', 'mov', 'wmv', 'avi', 'mpg', 'ogv', '3gp', '3g2']
      const audio = ['mp3', 'm4a', 'ogg', 'wav']
      const extension = path.split('.').slice(-1).pop()
      if (video.includes(extension)) {
        return 'video'
      } else if (audio.includes(extension)) {
        return 'audio'
      } else {
        return 'image'
      }
    }

    onSelect = (url) => {

      const { multiple, onSelect } = this.props

      this.state.media = []

      for(let i = 0, len = url.length; i < len; i++){
        this.state.media.push({
          id: i,
          caption: "",
          url: url[i].url,
          type: this.getMediaType(url[i].url)
        });
      }
      if(this.state.media.length > 0) {
        onSelect(multiple ? this.state.media : this.state.media[0])
      } else {
        onSelect([]);
      }
    }

    openModal = () => {
      let type = 'file'
      if (this.props.allowedTypes.length === 1 && this.props.allowedTypes[0] === 'image') {
        type = 'image'
      }
      this.openLFM(type, this.onSelect)
    }

    openLFM = (type, cb) => {
      let routePrefix = (config && config.prefix) ? config.prefix : '/laravel-filemanager'
      window.open(routePrefix + '?type=' + type, 'FileManager', 'width=900,height=600')
      window.SetUrl = cb
    }

    render () {
      const { render } = this.props
      return render({ open: this.openModal })
    }
  }

  hooks.addFilter(
    'editor.MediaUpload',
    'core/edit-post/components/media-upload/replace-media-upload',
    () => LaravelFilemanager
  )
}

/**
 * Registers a new block menu button
 *
 *

( function( wp ) {
    var withSelect = wp.data.withSelect;
    var ifCondition = wp.compose.ifCondition;
    var compose = wp.compose.compose;
    var MyCustomButton = function( props ) {
        return wp.element.createElement(
            wp.blockEditor.RichTextToolbarButton, {
                icon: 'editor-code',
                title: 'Sample output',
                onClick: function() {
                    console.log( 'toggle format' );
                },
            }
        );
    }
    var ConditionalButton = compose(
        withSelect( function( select ) {
            return {
                selectedBlock: select( 'core/editor' ).getSelectedBlock()
            }
        } ),
        ifCondition( function( props ) {
            if(props.selectedBlock) console.log(props.selectedBlock.name);
            return (
                props.selectedBlock &&
                props.selectedBlock.name === 'core/gallery'
            );
        } )
    )( MyCustomButton );

    wp.richText.registerFormatType(
        'my-custom-format/sample-output', {
            title: 'Sample output',
            tagName: 'samp',
            className: null,
            edit: ConditionalButton,
        }
    );
} )( window.wp );
*/
