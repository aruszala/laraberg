import { editorSettings, overridePost } from './settings'
import { configureAPI } from '../api/api-fetch'
import configureEditor from '../lib/configure-editor'
import { elementReady } from '../lib/element-ready'
import axios from 'axios'

const { blocks, data, domReady, editPost, i18n } = window.wp
const { unregisterBlockType, registerBlockType, getBlockType } = blocks

/**
 * Initialize the Gutenberg editor
 * @param {string} target the element ID to render the gutenberg editor in
 */
export default function init (target, options = {}) {
  configureAPI(options)

  // Disable publish sidebar
  data.dispatch('core/editor').disablePublishSidebar()

  // Disable tips
  data.dispatch('core/nux').disableTips()

  window._wpLoadGutenbergEditor = new Promise(function (resolve) {
    domReady(async () => {
      const larabergEditor = createEditorElement(target)
      try {
        getTranslations().then(()=>{
            resolve(editPost.initializeEditor(larabergEditor.id, 'page', 1, editorSettings, overridePost))
            fixReusableBlocks()
        });
      } catch (error) {
        console.error(error)
      }
      await elementReady('.edit-post-layout')
      configureEditor(options)
    })
  })
}

/**
 * Creates the element to render the Gutenberg editor inside of
 * @param {string} target the id of the textarea to render the Editor instead of
 * @return {element}
 */
function createEditorElement (target) {
  const element = document.getElementById(target)
  const editor = document.createElement('DIV')
  editor.id = 'laraberg__editor'
  editor.classList.add('laraberg__editor', 'gutenberg__editor', 'block-editor__container', 'wp-embed-responsive')
  element.parentNode.insertBefore(editor, element)
  element.hidden = true

  editorSettings.target = target
  window.Laraberg.editor = editor

  return editor
}

function fixReusableBlocks () {
  const coreBlock = getBlockType('core/block')
  unregisterBlockType('core/block')
  coreBlock.attributes = {
    ref: {
      type: 'number'
    }
  }
  registerBlockType('core/block', coreBlock)
}

function getTranslations () {
    return axios.get("/laraberg/i18n").then((response) => {
        if(typeof response.data.jed !== "undefined"){
            let domain = response.data.jed.domain;
            window.wp.i18n.setLocaleData(response.data.jed.locale_data[domain], "default");
        }
    });
}
