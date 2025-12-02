/*
  Requirement: Populate the resource detail page and discussion forum.
*/

// --- Global Data Store ---
let currentResourceId = null;
let currentComments = [];

// --- Element Selections ---
const resourceTitle = document.getElementById("resource-title");
const resourceDescription = document.getElementById("resource-description");
const resourceLink = document.getElementById("resource-link");
const commentList = document.getElementById("comment-list");
const commentForm = document.getElementById("comment-form");
const newComment = document.getElementById("new-comment");

// --- Functions ---

function getResourceIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get("id");
}

function renderResourceDetails(resource) {
  resourceTitle.textContent = resource.title;
  resourceDescription.textContent = resource.description;
  resourceLink.href = resource.link;
}

function createCommentArticle(comment) {
  const article = document.createElement("article");

  const p = document.createElement("p");
  p.textContent = comment.text;

  const footer = document.createElement("footer");
  footer.textContent = `Posted by: ${comment.author}`;

  article.appendChild(p);
  article.appendChild(footer);

  return article;
}

function renderComments() {
  commentList.innerHTML = "";
  currentComments.forEach((comment) => {
    const commentArticle = createCommentArticle(comment);
    commentList.appendChild(commentArticle);
  });
}

function handleAddComment(event) {
  event.preventDefault();

  const commentText = newComment.value.trim();
  if (!commentText) return;

  const newCommentObj = {
    author: "Student",
    text: commentText,
  };

  currentComments.push(newCommentObj);
  renderComments();
  newComment.value = "";
}

async function initializePage() {
  currentResourceId = getResourceIdFromURL();

  if (!currentResourceId) {
    resourceTitle.textContent = "Resource not found.";
    return;
  }

  try {
    const [resourcesRes, commentsRes] = await Promise.all([
      fetch("resources.json"),
      fetch("resource-comments.json")
    ]);

    const resourcesData = await resourcesRes.json();
    const commentsData = await commentsRes.json();

    const resource = resourcesData.find(
      (res) => res.id === currentResourceId
    );

    currentComments = commentsData[currentResourceId] || [];

    if (resource) {
      renderResourceDetails(resource);
      renderComments();
      commentForm.addEventListener("submit", handleAddComment);
    } else {
      resourceTitle.textContent = "Resource not found.";
    }

  } catch (error) {
    resourceTitle.textContent = "Error loading resource.";
  }
}

// --- Initial Page Load ---
initializePage();
